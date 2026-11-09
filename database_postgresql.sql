-- Event Registration & Admin Management System Database Schema (PostgreSQL)

-- Admin Users Table
CREATE TABLE admin_users (
  id SERIAL PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO admin_users (username, password_hash) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Events Table
CREATE TABLE events (
  id SERIAL PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT,
  date DATE NOT NULL,
  time TIME NOT NULL,
  venue TEXT NOT NULL,
  banner_image VARCHAR(255) DEFAULT NULL,
  youtube_url VARCHAR(500) DEFAULT NULL,
  is_paid BOOLEAN NOT NULL DEFAULT FALSE,
  price DECIMAL(10,2) DEFAULT 0.00,
  max_registrations INTEGER DEFAULT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'upcoming' CHECK (status IN ('upcoming','live','past')),
  is_hidden BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Registrations Table
CREATE TABLE registrations (
  id SERIAL PRIMARY KEY,
  event_id INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  otp_verified BOOLEAN NOT NULL DEFAULT FALSE,
  payment_id VARCHAR(255) DEFAULT NULL,
  payment_status VARCHAR(50) DEFAULT 'pending',
  utm_source VARCHAR(100) DEFAULT NULL,
  utm_medium VARCHAR(100) DEFAULT NULL,
  utm_campaign VARCHAR(100) DEFAULT NULL,
  ip_address INET DEFAULT NULL,
  user_agent TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_registrations_event_id ON registrations(event_id);
CREATE INDEX idx_registrations_email_event ON registrations(email, event_id);

-- OTP Verification Table (temporary storage)
CREATE TABLE otp_verifications (
  id SERIAL PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  event_id INTEGER NOT NULL,
  otp_hash VARCHAR(255) NOT NULL,
  expires_at TIMESTAMP NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_otp_email_event ON otp_verifications(email, event_id);

-- Event Views/Analytics Table
CREATE TABLE event_views (
  id SERIAL PRIMARY KEY,
  event_id INTEGER DEFAULT NULL REFERENCES events(id) ON DELETE SET NULL,
  page_name VARCHAR(100) NOT NULL,
  utm_source VARCHAR(100) DEFAULT NULL,
  utm_medium VARCHAR(100) DEFAULT NULL,
  utm_campaign VARCHAR(100) DEFAULT NULL,
  referrer TEXT DEFAULT NULL,
  ip_address INET DEFAULT NULL,
  user_agent TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_event_views_event_id ON event_views(event_id);
CREATE INDEX idx_event_views_created_at ON event_views(created_at);

-- YouTube Video Interactions Table
CREATE TABLE youtube_interactions (
  id SERIAL PRIMARY KEY,
  event_id INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
  video_id VARCHAR(50) NOT NULL,
  interaction_type VARCHAR(20) NOT NULL CHECK (interaction_type IN ('play','pause','ended')),
  timestamp_seconds INTEGER DEFAULT NULL,
  utm_source VARCHAR(100) DEFAULT NULL,
  utm_medium VARCHAR(100) DEFAULT NULL,
  utm_campaign VARCHAR(100) DEFAULT NULL,
  ip_address INET DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_youtube_interactions_event_id ON youtube_interactions(event_id);

-- Event Gallery Table
CREATE TABLE event_gallery (
  id SERIAL PRIMARY KEY,
  event_id INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
  media_type VARCHAR(10) NOT NULL CHECK (media_type IN ('photo','video')),
  media_name VARCHAR(255) NOT NULL,
  media_path VARCHAR(500) NOT NULL,
  file_size BIGINT DEFAULT NULL,
  uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_event_gallery_event_id ON event_gallery(event_id);

-- Payments Table (for Razorpay integration)
CREATE TABLE payments (
  id SERIAL PRIMARY KEY,
  registration_id INTEGER NOT NULL REFERENCES registrations(id) ON DELETE CASCADE,
  razorpay_payment_id VARCHAR(255) DEFAULT NULL,
  razorpay_order_id VARCHAR(255) DEFAULT NULL,
  amount DECIMAL(10,2) NOT NULL,
  currency VARCHAR(3) DEFAULT 'INR',
  status VARCHAR(50) DEFAULT 'pending',
  payment_method VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_payments_registration_id ON payments(registration_id);
CREATE INDEX idx_payments_razorpay_payment_id ON payments(razorpay_payment_id);

-- Function to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Create trigger for events table
CREATE TRIGGER update_events_updated_at BEFORE UPDATE ON events
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Create trigger for payments table
CREATE TRIGGER update_payments_updated_at BEFORE UPDATE ON payments
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Insert sample events for testing
INSERT INTO events (title, slug, description, date, time, venue, is_paid, price, status) VALUES
('Tech Conference 2025', 'tech-conference-2025', 'Annual technology conference featuring latest innovations and industry insights.', '2025-08-15', '09:00:00', 'Convention Center, Tech Park, Bangalore', true, 2499.00, 'upcoming'),
('Free Photography Workshop', 'free-photography-workshop', 'Learn basic photography techniques and composition rules in this hands-on workshop.', '2025-07-30', '14:00:00', 'Community Center, MG Road, Bangalore', false, 0.00, 'upcoming'),
('Startup Networking Event', 'startup-networking-event', 'Connect with fellow entrepreneurs, investors, and industry experts.', '2025-08-05', '18:30:00', 'Hotel Leela Palace, Bangalore', true, 1299.00, 'upcoming');