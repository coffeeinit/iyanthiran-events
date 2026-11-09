-- MySQL Database Schema for Event Registration System

-- Users table (for admin authentication)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'moderator') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Events table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    time TIME NOT NULL,
    venue VARCHAR(255) NOT NULL,
    banner_image VARCHAR(255),
    youtube_url VARCHAR(255),
    is_paid BOOLEAN DEFAULT FALSE,
    price DECIMAL(10,2) DEFAULT 0.00,
    max_registrations INT,
    status ENUM('upcoming', 'ongoing', 'past', 'cancelled') DEFAULT 'upcoming',
    is_hidden BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Registrations table
CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    payment_id VARCHAR(255),
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    otp_verified BOOLEAN DEFAULT FALSE,
    utm_source VARCHAR(100),
    utm_medium VARCHAR(100),
    utm_campaign VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_event_id (event_id),
    INDEX idx_email (email),
    INDEX idx_payment_status (payment_status)
);

-- OTP Verifications table
CREATE TABLE IF NOT EXISTS otp_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    event_id INT NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_email_event (email, event_id),
    INDEX idx_expires (expires_at)
);

-- Gallery table
CREATE TABLE IF NOT EXISTS gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    media_type ENUM('photo', 'video') NOT NULL,
    media_path VARCHAR(255) NOT NULL,
    caption TEXT,
    uploaded_by VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_event_id (event_id)
);

-- Event Views table (for analytics)
CREATE TABLE IF NOT EXISTS event_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT,
    page_name VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    referrer VARCHAR(255),
    user_agent TEXT,
    utm_source VARCHAR(100),
    utm_medium VARCHAR(100),
    utm_campaign VARCHAR(100),
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    INDEX idx_event_id (event_id),
    INDEX idx_page_name (page_name),
    INDEX idx_viewed_at (viewed_at)
);

-- Admin Sessions table
CREATE TABLE IF NOT EXISTS admin_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_expires (expires_at)
);

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO users (username, email, password_hash, role) VALUES 
('admin', 'admin@iyanthiran.com', '$2y$10$8E8yQr8t9mR3o5ZNvY4vK.MkGUQaB4qJ8X3C2VzT1hP9fN6eR5sKW', 'admin');

-- Insert sample events
INSERT IGNORE INTO events (title, slug, description, date, time, venue, is_paid, price, max_registrations, status, is_hidden) VALUES 
('Free Workshop: Web Development Basics', 'free-workshop-web-development-basics', 'Learn the fundamentals of web development including HTML, CSS, and JavaScript. Perfect for beginners!', '2025-08-15', '14:00:00', 'Community Center, Main Hall', FALSE, 0.00, 50, 'upcoming', FALSE),

('Tech Conference 2025', 'tech-conference-2025', 'Join us for a full-day conference featuring the latest in technology trends, AI, and innovation. Includes lunch and networking sessions.', '2025-09-20', '09:00:00', 'Grand Convention Center', TRUE, 2499.00, 200, 'upcoming', FALSE),

('Startup Networking Event', 'startup-networking-event', 'Connect with fellow entrepreneurs, investors, and tech enthusiasts. Great opportunity to pitch your ideas and find co-founders.', '2025-08-30', '18:30:00', 'Innovation Hub, 5th Floor', FALSE, 0.00, 75, 'upcoming', FALSE),

('Digital Marketing Masterclass', 'digital-marketing-masterclass', 'Advanced strategies for social media marketing, SEO, and content creation. Includes hands-on exercises and templates.', '2025-09-10', '10:00:00', 'Business Center, Room 301', TRUE, 1999.00, 30, 'upcoming', FALSE),

('Coding Bootcamp Weekend', 'coding-bootcamp-weekend', 'Intensive 2-day programming bootcamp covering Python, databases, and web frameworks. All skill levels welcome.', '2025-10-05', '09:00:00', 'Tech Academy Campus', TRUE, 4999.00, 25, 'upcoming', FALSE);