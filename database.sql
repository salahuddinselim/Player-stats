-- Create database
CREATE DATABASE IF NOT EXISTS player_stats;
USE player_stats;

-- Players table
CREATE TABLE players (
    player_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    profile_photo VARCHAR(255),
    age INT,
    nationality VARCHAR(100),
    team_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Matches table
CREATE TABLE matches (
    match_id INT PRIMARY KEY AUTO_INCREMENT,
    tournament_id INT,
    date DATE,
    home_team VARCHAR(100),
    away_team VARCHAR(100),
    home_score INT DEFAULT 0,
    away_score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Player statistics table
CREATE TABLE player_stats (
    stat_id INT PRIMARY KEY AUTO_INCREMENT,
    player_id INT,
    match_id INT,
    goals INT DEFAULT 0,
    assists INT DEFAULT 0,
    minutes_played INT DEFAULT 0,
    yellow_cards INT DEFAULT 0,
    red_cards INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES players(player_id),
    FOREIGN KEY (match_id) REFERENCES matches(match_id)
);

-- Tournaments table
CREATE TABLE tournaments (
    tournament_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    season_year INT,
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Player achievements table
CREATE TABLE achievements (
    achievement_id INT PRIMARY KEY AUTO_INCREMENT,
    player_id INT,
    title VARCHAR(255),
    description TEXT,
    date_achieved DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES players(player_id)
);

-- Insert sample players
INSERT INTO players (name, profile_photo, age, nationality, team_name) VALUES
('Monirul01', 'pictures/IMG-20210228-WA0010.jpg', 23, 'Bangladesh', 'Tigers FC'),
('Monirul02', 'pictures/IMG-20210416-WA0003.jpg', 25, 'Bangladesh', 'Lions FC'),
('Monirul03', 'pictures/IMG-20210516-WA0013.jpg', 22, 'Bangladesh', 'Eagles FC'),
('Monirul04', 'pictures/IMG-20210802-WA0001.jpg', 24, 'Bangladesh', 'Tigers FC'),
('Monirul05', 'pictures/IMG-20220416-WA0001.jpg', 26, 'Bangladesh', 'Lions FC');

-- Insert sample tournaments
INSERT INTO tournaments (name, season_year, start_date, end_date) VALUES
('Premier League', 2024, '2024-01-01', '2024-12-31'),
('Cup Championship', 2024, '2024-03-01', '2024-05-31'),
('Super League', 2024, '2024-06-01', '2024-08-31');

-- Insert sample matches
INSERT INTO matches (tournament_id, date, home_team, away_team, home_score, away_score) VALUES
(1, '2024-01-10', 'Tigers FC', 'Lions FC', 3, 1),
(1, '2024-01-15', 'Eagles FC', 'Tigers FC', 1, 2),
(1, '2024-01-20', 'Lions FC', 'Eagles FC', 2, 1),
(2, '2024-03-05', 'Tigers FC', 'Eagles FC', 4, 1),
(2, '2024-03-10', 'Lions FC', 'Tigers FC', 2, 3);

-- Insert sample player statistics
INSERT INTO player_stats (player_id, match_id, goals, assists, minutes_played, yellow_cards, red_cards) VALUES
-- Monirul01 stats
(1, 1, 2, 1, 90, 0, 0),
(1, 2, 1, 2, 85, 1, 0),
(1, 4, 3, 0, 90, 0, 0),

-- Monirul02 stats
(2, 1, 1, 1, 90, 1, 0),
(2, 3, 2, 1, 90, 0, 0),
(2, 5, 1, 2, 75, 0, 0),

-- Monirul03 stats
(3, 2, 0, 2, 90, 0, 0),
(3, 3, 1, 1, 90, 1, 0),
(3, 4, 1, 0, 65, 0, 0),

-- Monirul04 stats
(4, 1, 0, 2, 90, 0, 0),
(4, 2, 2, 0, 90, 0, 0),
(4, 4, 1, 1, 90, 1, 0),

-- Monirul05 stats
(5, 1, 1, 0, 75, 0, 0),
(5, 3, 0, 2, 90, 0, 0),
(5, 5, 2, 1, 90, 1, 0);

-- Insert sample achievements
INSERT INTO achievements (player_id, title, description, date_achieved) VALUES
(1, 'Hat-trick Hero', 'Scored first hat-trick in Premier League', '2024-01-10'),
(1, 'Golden Boot', 'Top scorer in Cup Championship', '2024-03-10'),
(2, 'Playmaker Award', 'Most assists in Premier League', '2024-01-20'),
(3, 'Rising Star', 'Best young player of the season', '2024-01-15'),
(4, 'Clean Sheet King', 'Most clean sheets in a season', '2024-03-05'),
(5, 'MVP', 'Most Valuable Player in Super League', '2024-03-10');
