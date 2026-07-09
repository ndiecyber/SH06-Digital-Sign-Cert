import mysql from 'mysql2/promise';
import dotenv from 'dotenv';

dotenv.config();

// Create connection without db name first to ensure db exists
const setupDatabase = async () => {
    try {
        const connection = await mysql.createConnection({
            host: process.env.DB_HOST || 'localhost',
            port: process.env.DB_PORT || 3306,
            user: process.env.DB_USER || 'root',
            password: process.env.DB_PASSWORD || '',
        });

        await connection.query(`CREATE DATABASE IF NOT EXISTS \`${process.env.DB_NAME || 'lexa_db'}\``);
        await connection.end();
        console.log(`Database '${process.env.DB_NAME || 'lexa_db'}' verified/created.`);
    } catch (err) {
        console.error('Error creating database:', err.message);
    }
};

await setupDatabase();

// Main connection pool
const db = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    port: process.env.DB_PORT || 3306,
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'lexa_db',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});

// Setup tables DDL
const setupTables = async () => {
    try {
        // 1. Users Table
        await db.query(`
            CREATE TABLE IF NOT EXISTS users (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'user',
                plan VARCHAR(50) DEFAULT 'free',
                avatar LONGTEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        `);

        // Dynamically add column for existing schemas
        try {
            await db.query('ALTER TABLE users ADD COLUMN avatar LONGTEXT');
        } catch (e) {
            // Ignore if column already exists
        }

        // 2. Documents Table
        await db.query(`
            CREATE TABLE IF NOT EXISTS documents (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                type VARCHAR(100) NOT NULL,
                status VARCHAR(50) DEFAULT 'pending',
                uploaded_by_name VARCHAR(255) NOT NULL,
                uploaded_by_email VARCHAR(255) NOT NULL,
                target_signer_email VARCHAR(255),
                date VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        `);

        // 3. Document Signers Table
        await db.query(`
            CREATE TABLE IF NOT EXISTS document_signers (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                document_id BIGINT NOT NULL,
                email VARCHAR(255) NOT NULL,
                status VARCHAR(50) DEFAULT 'pending',
                KEY (document_id)
            )
        `);

        // 4. Certificates Table
        await db.query(`
            CREATE TABLE IF NOT EXISTS certificates (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                holder VARCHAR(255) NOT NULL,
                issued_at VARCHAR(100) NOT NULL,
                valid_until VARCHAR(100) NOT NULL,
                status VARCHAR(50) DEFAULT 'valid',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        `);

        // 5. Teams Table
        await db.query(`
            CREATE TABLE IF NOT EXISTS teams (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                members TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        `);

        // 6. Activity Logs Table
        await db.query(`
            CREATE TABLE IF NOT EXISTS activity_logs (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_name VARCHAR(255) NOT NULL,
                action VARCHAR(100) NOT NULL,
                description TEXT,
                date VARCHAR(100),
                time VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        `);

        // Insert default accounts if users table is empty
        const [rows] = await db.query('SELECT COUNT(*) as count FROM users');
        if (rows[0].count === 0) {
            await db.query(`
                INSERT INTO users (name, email, password, role, plan) VALUES
                ('Administrator', 'admin@lexa.com', 'password', 'admin', 'enterprise'),
                ('Rizky Pratama', 'user@lexa.com', 'password', 'user', 'free'),
                ('Rachel', 'rachel@lexa.com', 'password', 'user', 'free')
            `);
            console.log('Default accounts initialized in users table.');
        }

        console.log('Database tables verified/created successfully.');
    } catch (err) {
        console.error('Error verifying database tables:', err.message);
    }
};

await setupTables();

export default db;
