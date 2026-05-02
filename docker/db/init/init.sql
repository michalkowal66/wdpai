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
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (desk_id, booking_date)
);