CREATE TYPE user_role AS ENUM ('ADMIN', 'EMPLOYEE');
CREATE TYPE booking_status AS ENUM ('ACTIVE', 'CHECKED_IN', 'CANCELLED', 'NO_SHOW');

CREATE TABLE users (
    id SERIAL PRIMARY KEY,    
    email VARCHAR(255) UNIQUE NOT NULL,    
    password TEXT NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    job_title VARCHAR(255),
    role user_role DEFAULT 'EMPLOYEE',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT FALSE
);

CREATE TABLE floors (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL, -- e.g. 'North Wing'
    level INT UNIQUE NOT NULL, -- e.g. 4
    map_image_url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE desks (
    id SERIAL PRIMARY KEY,
    identifier VARCHAR(50) NOT NULL, -- e.g. 'Desk A1'
    description TEXT, -- e.g. 'Premium Window View'
    floor_id INT NOT NULL REFERENCES floors(id) ON DELETE CASCADE,
    pos_x DECIMAL(5,2) NOT NULL, -- Percentage 0.00 to 100.00
    pos_y DECIMAL(5,2) NOT NULL, -- Percentage 0.00 to 100.00
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (identifier, floor_id)
);

CREATE TABLE features (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL, -- e.g. 'Strong WiFi'
    icon_name VARCHAR(50) NOT NULL -- Material Symbols icon name, e.g. 'wifi'
);

CREATE TABLE desk_features (
    desk_id INT REFERENCES desks(id) ON DELETE CASCADE,
    feature_id INT REFERENCES features(id) ON DELETE CASCADE,
    PRIMARY KEY (desk_id, feature_id)
);

CREATE TABLE desk_maintenances (
    id SERIAL PRIMARY KEY,
    desk_id INT NOT NULL REFERENCES desks(id) ON DELETE CASCADE,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CHECK (end_date >= start_date)
);

CREATE TABLE bookings (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    desk_id INT NOT NULL REFERENCES desks(id) ON DELETE CASCADE,
    booking_date DATE NOT NULL,
    status booking_status DEFAULT 'ACTIVE',
    checked_in_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE login_attempts (
    id SERIAL PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(255) NOT NULL,
    attempted_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_login_attempts_ip ON login_attempts(ip_address, attempted_at);

CREATE UNIQUE INDEX unique_active_desk_booking ON bookings (desk_id, booking_date) WHERE status IN ('ACTIVE', 'CHECKED_IN');
CREATE UNIQUE INDEX unique_active_user_booking ON bookings (user_id, booking_date) WHERE status IN ('ACTIVE', 'CHECKED_IN');

-- =========================================
-- DATABASE VIEWS (Analytics)
-- =========================================

-- 1. View: Desk Popularity
CREATE VIEW view_desk_popularity AS
SELECT 
    d.id AS desk_id,
    d.identifier,
    f.name AS floor_name,
    COUNT(b.id) AS total_bookings,
    COUNT(CASE WHEN b.status = 'CHECKED_IN' THEN 1 END) AS successful_bookings
FROM desks d
JOIN floors f ON d.floor_id = f.id
LEFT JOIN bookings b ON d.id = b.desk_id
GROUP BY d.id, d.identifier, f.name
ORDER BY total_bookings DESC;

-- 2. View: User Attendance and Reliability
CREATE VIEW view_user_attendance AS
SELECT 
    u.id AS user_id,
    u.full_name,
    u.job_title,
    COUNT(b.id) AS total_reservations,
    COUNT(CASE WHEN b.status = 'CHECKED_IN' THEN 1 END) AS check_ins,
    COUNT(CASE WHEN b.status = 'CANCELLED' THEN 1 END) AS cancellations,
    COUNT(CASE WHEN b.status = 'NO_SHOW' THEN 1 END) AS no_shows,
    CASE 
        WHEN COUNT(b.id) > 0 THEN 
            ROUND((COUNT(CASE WHEN b.status = 'CHECKED_IN' THEN 1 END)::numeric / COUNT(b.id)::numeric) * 100, 2)
        ELSE 0 
    END AS reliability_score
FROM users u
LEFT JOIN bookings b ON u.id = b.user_id
GROUP BY u.id, u.full_name, u.job_title
ORDER BY check_ins DESC;

-- 3. View: Feature Popularity
CREATE VIEW view_feature_popularity AS
SELECT 
    f.id AS feature_id,
    f.name AS feature_name,
    f.icon_name,
    COUNT(b.id) AS total_bookings
FROM features f
JOIN desk_features df ON f.id = df.feature_id
JOIN bookings b ON df.desk_id = b.desk_id
GROUP BY f.id, f.name, f.icon_name
ORDER BY total_bookings DESC;

-- =========================================
-- DATABASE FUNCTIONS & TRIGGERS
-- =========================================

-- Function: Prevent bookings in the past
CREATE OR REPLACE FUNCTION prevent_past_bookings()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.booking_date < CURRENT_DATE THEN
        RAISE EXCEPTION 'Cannot create or update a booking for a past date.';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger: Execute function before insert or update on bookings
CREATE TRIGGER trigger_check_booking_date
BEFORE INSERT OR UPDATE ON bookings
FOR EACH ROW
EXECUTE FUNCTION prevent_past_bookings();