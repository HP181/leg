-- Create the database
CREATE DATABASE IF NOT EXISTS parliament_system;
USE parliament_system;

-- Users table
CREATE TABLE users (
    username VARCHAR(50) PRIMARY KEY,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Reviewer', 'MP') NOT NULL,
);

-- Bills table
CREATE TABLE bills (
    id VARCHAR(50) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    author VARCHAR(50),
    draft TEXT,
    status ENUM('Draft', 'Under Review', 'Review Complete', 'Voting Started', 'Passed', 'Rejected') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    review_completed_at DATETIME NULL,
    reviewed_by VARCHAR(50) NULL,
    voting_finalized_at DATETIME NULL,
    FOREIGN KEY (author) REFERENCES users(username),
    FOREIGN KEY (reviewed_by) REFERENCES users(username)
);

-- Bill history table
CREATE TABLE bill_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id VARCHAR(50),
    title VARCHAR(255),
    description TEXT,
    draft TEXT,
    status ENUM('Draft', 'Under Review', 'Review Complete', 'Voting Started', 'Passed', 'Rejected'),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    changed_by VARCHAR(50),
    FOREIGN KEY (bill_id) REFERENCES bills(id),
    FOREIGN KEY (changed_by) REFERENCES users(username)
);

-- Votes table
CREATE TABLE votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id VARCHAR(50),
    username VARCHAR(50),
    vote ENUM('For', 'Against', 'Abstain') NOT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bill_id) REFERENCES bills(id),
    FOREIGN KEY (username) REFERENCES users(username),
    UNIQUE KEY unique_vote (bill_id, username)
);

-- Amendments table
CREATE TABLE amendments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id VARCHAR(50),
    reviewer VARCHAR(50),
    amendment_text TEXT NOT NULL,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bill_id) REFERENCES bills(id),
    FOREIGN KEY (reviewer) REFERENCES users(username)
);

-- Optional: Add indexes for better performance
-- CREATE INDEX idx_bills_status ON bills(status);
-- CREATE INDEX idx_bills_author ON bills(author);
-- CREATE INDEX idx_amendments_bill ON amendments(bill_id);
-- CREATE INDEX idx_votes_bill ON votes(bill_id);