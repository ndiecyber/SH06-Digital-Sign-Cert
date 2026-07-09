import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import db from './db.js';

dotenv.config();

const app = express();
const PORT = process.env.PORT || 5000;

app.use(cors());
app.use(express.json());

// --- AUTH API ---

// Login
app.post('/api/auth/login', async (req, res) => {
    const { email, password } = req.body;
    try {
        const [rows] = await db.query('SELECT * FROM users WHERE email = ? AND password = ?', [email, password]);
        if (rows.length > 0) {
            const { password: _, ...userSession } = rows[0];
            return res.json({ success: true, user: userSession });
        }
        return res.status(401).json({ success: false, message: 'Email atau password salah.' });
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
});

// Register
app.post('/api/auth/register', async (req, res) => {
    const { name, email, password } = req.body;
    try {
        // Check uniqueness
        const [existing] = await db.query('SELECT * FROM users WHERE email = ?', [email]);
        if (existing.length > 0) {
            return res.status(400).json({ success: false, message: 'Email sudah terdaftar.' });
        }

        const [result] = await db.query(
            'INSERT INTO users (name, email, password, role, plan) VALUES (?, ?, ?, "user", "free")',
            [name, email, password]
        );

        const newUser = { id: result.insertId, name, email, role: 'user', plan: 'free' };
        return res.json({ success: true, user: newUser });
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
});

// Update profile settings
app.put('/api/auth/user', async (req, res) => {
    const { email, name, role, avatar } = req.body;
    try {
        await db.query(
            'UPDATE users SET name = ?, role = ?, avatar = ? WHERE email = ?',
            [name, role, avatar, email]
        );
        const [rows] = await db.query('SELECT * FROM users WHERE email = ?', [email]);
        const { password: _, ...userSession } = rows[0];
        return res.json({ success: true, user: userSession });
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
});

// Upgrade user plan
app.put('/api/auth/upgrade-plan', async (req, res) => {
    const { email, plan } = req.body;
    try {
        await db.query('UPDATE users SET plan = ? WHERE email = ?', [plan, email]);
        const [rows] = await db.query('SELECT * FROM users WHERE email = ?', [email]);
        const { password: _, ...userSession } = rows[0];
        return res.json({ success: true, user: userSession });
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
});

// Get all registered users list
app.get('/api/auth/users', async (req, res) => {
    try {
        const [rows] = await db.query('SELECT id, name, email, role, plan, created_at FROM users');
        return res.json(rows);
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
});

// --- DOCUMENTS API ---

// Get all documents
app.get('/api/documents', async (req, res) => {
    try {
        const [docs] = await db.query('SELECT * FROM documents');
        
        // Load target signers for each document
        const enrichedDocs = await Promise.all(docs.map(async (doc) => {
            const [signers] = await db.query('SELECT email, status FROM document_signers WHERE document_id = ?', [doc.id]);
            return {
                ...doc,
                target_signers: signers,
                // fallback properties used in frontend
                target_signer_email: signers.length > 0 ? signers[0].email : ''
            };
        }));
        
        return res.json(enrichedDocs);
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
});

// Upload/Create document
app.post('/api/documents', async (req, res) => {
    const { title, type, uploaded_by, target_signer_emails } = req.body;
    try {
        const dateString = new Date().toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        
        const [result] = await db.query(
            'INSERT INTO documents (title, type, status, uploaded_by_name, uploaded_by_email, date) VALUES (?, ?, "pending", ?, ?, ?)',
            [title, type, uploaded_by.name, uploaded_by.email, dateString]
        );
        const docId = result.insertId;

        // Insert target signers
        if (Array.isArray(target_signer_emails)) {
            for (const email of target_signer_emails) {
                if (email) {
                    await db.query(
                        'INSERT INTO document_signers (document_id, email, status) VALUES (?, ?, "pending")',
                        [docId, email]
                    );
                }
            }
        }

        const [newDoc] = await db.query('SELECT * FROM documents WHERE id = ?', [docId]);
        const [signers] = await db.query('SELECT email, status FROM document_signers WHERE document_id = ?', [docId]);
        
        return res.json({
            success: true,
            document: {
                ...newDoc[0],
                target_signers: signers,
                target_signer_email: signers.length > 0 ? signers[0].email : ''
            }
        });
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
});

// Request signature for existing document
app.post('/api/documents/:id/request-signer', async (req, res) => {
    const { id } = req.params;
    const { email } = req.body;
    try {
        await db.query(
            'INSERT INTO document_signers (document_id, email, status) VALUES (?, ?, "pending")',
            [id, email]
        );
        await db.query('UPDATE documents SET status = "pending" WHERE id = ?', [id]);
        return res.json({ success: true });
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
});

// Delete document
app.delete('/api/documents/:id', async (req, res) => {
    const { id } = req.params;
    try {
        await db.query('DELETE FROM documents WHERE id = ?', [id]);
        await db.query('DELETE FROM document_signers WHERE document_id = ?', [id]);
        return res.json({ success: true });
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
});

// Sign document
app.post('/api/documents/:id/sign', async (req, res) => {
    const { id } = req.params;
    const { email } = req.body;
    try {
        // Update signer status
        await db.query(
            'UPDATE document_signers SET status = "signed" WHERE document_id = ? AND email = ?',
            [id, email]
        );

        // Check if all signers of this document have signed
        const [signers] = await db.query('SELECT status FROM document_signers WHERE document_id = ?', [id]);
        const allSigned = signers.every(s => s.status === 'signed');
        
        if (allSigned) {
            await db.query('UPDATE documents SET status = "signed" WHERE id = ?', [id]);
        }

        return res.json({ success: true });
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
});

// --- CERTIFICATES API ---

// Get all certificates
app.get('/api/certificates', async (req, res) => {
    try {
        const [rows] = await db.query('SELECT * FROM certificates');
        return res.json(rows);
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
});

// Issue certificate
app.post('/api/certificates', async (req, res) => {
    const { name, holder, validityDays } = req.body;
    try {
        const issued_at = new Date().toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        const valid_until = new Date(Date.now() + validityDays * 86400000).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

        const [result] = await db.query(
            'INSERT INTO certificates (name, holder, issued_at, valid_until, status) VALUES (?, ?, ?, ?, "valid")',
            [name, holder, issued_at, valid_until]
        );

        const newCert = { id: result.insertId, name, holder, issued_at, valid_until, status: 'valid' };
        return res.json({ success: true, certificate: newCert });
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
});

// Delete certificate
app.delete('/api/certificates/:id', async (req, res) => {
    const { id } = req.params;
    try {
        await db.query('DELETE FROM certificates WHERE id = ?', [id]);
        return res.json({ success: true });
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
});

// --- TEAMS API ---

// Get all teams
app.get('/api/teams', async (req, res) => {
    try {
        const [rows] = await db.query('SELECT * FROM teams');
        const formatted = rows.map(r => ({
            ...r,
            members: JSON.parse(r.members || '[]')
        }));
        return res.json(formatted);
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
});

// Create/Update team
app.post('/api/teams', async (req, res) => {
    const { id, name, members } = req.body;
    const membersString = JSON.stringify(members || []);
    try {
        if (id) {
            // Update existing
            await db.query('UPDATE teams SET name = ?, members = ? WHERE id = ?', [name, membersString, id]);
            return res.json({ success: true });
        } else {
            // Insert new
            const [result] = await db.query('INSERT INTO teams (name, members) VALUES (?, ?)', [name, membersString]);
            return res.json({ success: true, id: result.insertId });
        }
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
});

// Delete team
app.delete('/api/teams/:id', async (req, res) => {
    const { id } = req.params;
    try {
        await db.query('DELETE FROM teams WHERE id = ?', [id]);
        return res.json({ success: true });
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
});

// --- ACTIVITIES API ---

// Get activities
app.get('/api/activities', async (req, res) => {
    try {
        const [rows] = await db.query('SELECT * FROM activity_logs ORDER BY id DESC');
        return res.json(rows);
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
});

// Log activity
app.post('/api/activities', async (req, res) => {
    const { user_name, action, description } = req.body;
    try {
        const dateStr = new Date().toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';

        const [result] = await db.query(
            'INSERT INTO activity_logs (user_name, action, description, date, time) VALUES (?, ?, ?, ?, ?)',
            [user_name, action, description, dateStr, timeStr]
        );

        return res.json({ success: true, id: result.insertId });
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
});

app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});
