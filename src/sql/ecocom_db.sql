CREATE DATABASE IF NOT EXISTS ecocom_db;

USE ecocom_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    birthdate DATE NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE
);
CREATE TABLE IF NOT EXISTS swaps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    description VARCHAR(3000),
    category VARCHAR(50),
    image_url VARCHAR(255) NOT NULL,
    wish_list VARCHAR(3000),
    user_notes VARCHAR(3000),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS swap_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    requested_item_id INT NOT NULL,
    offered_item_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_item_id) REFERENCES swaps(id) ON DELETE CASCADE,
    FOREIGN KEY (offered_item_id) REFERENCES swaps(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    related_id INT,
    type ENUM('new_item', 'swap_request', 'request_accepted', 'request_rejected', 'garden_post', 'garden_join', 'garden_exchange') NOT NULL,
    message VARCHAR(3000),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    content VARCHAR(3000) NOT NULL,
    author_id INT NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS gardens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    address VARCHAR(255) NOT NULL,
    description VARCHAR(3000),
    start_date DATETIME,
    end_date DATETIME,
    recurring_day VARCHAR(20),
    recurring_start_time TIME,
    recurring_end_time TIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS garden_participants (
    garden_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (garden_id, user_id),
    FOREIGN KEY (garden_id) REFERENCES gardens(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS garden_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    garden_id INT NOT NULL,
    content VARCHAR(3000) NOT NULL,
    image_url VARCHAR(255),
    is_exchangeable BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (garden_id) REFERENCES gardens(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS recycling (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description VARCHAR(3000),
    location VARCHAR(255) NOT NULL,
    item_to_recycle VARCHAR(3000),
    contact VARCHAR(100),
    event_date DATETIME NOT NULL,
    event_end_date DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS recycling_participants (
    user_id INT NOT NULL,
    recycling_id INT NOT NULL,
    PRIMARY KEY (recycling_id, user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recycling_id) REFERENCES recycling(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS garden_exchange (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    requested_post_id INT NOT NULL,
    offered_post_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_post_id) REFERENCES garden_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (offered_post_id) REFERENCES garden_posts(id) ON DELETE CASCADE
);



CREATE TABLE IF NOT EXISTS user_favourites (
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    PRIMARY KEY (user_id, item_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES swaps(id) ON DELETE CASCADE
);

CREATE INDEX idx_swaps_user_id ON swaps(user_id);
CREATE INDEX idx_swaps_category ON swaps(category);
CREATE INDEX idx_swap_requests_requester ON swap_requests(requester_id);
CREATE INDEX idx_swap_requests_requested_item ON swap_requests(requested_item_id);
CREATE INDEX idx_swap_requests_offered_item ON swap_requests(offered_item_id);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_type ON notifications(type);
CREATE INDEX idx_blog_posts_author ON blog_posts(author_id);
CREATE INDEX idx_garden_posts_user ON garden_posts(user_id);
CREATE INDEX idx_garden_posts_garden ON garden_posts(garden_id);
CREATE INDEX idx_gardens_user ON gardens(user_id);
CREATE INDEX idx_gardens_dates ON gardens(start_date, end_date);
CREATE INDEX idx_garden_exchange_requester ON garden_exchange(requester_id);
CREATE INDEX idx_garden_exchange_requested_post ON garden_exchange(requested_post_id);
CREATE INDEX idx_garden_exchange_offered_post ON garden_exchange(offered_post_id);