# Player Statistics Dashboard

A responsive web application for displaying player statistics in a tournament management system.

## Features

- Responsive design for both desktop and mobile devices
- Real-time player search functionality
- Detailed player statistics and performance metrics
- Interactive charts and visualizations
- Recent match history
- Career milestones and achievements

## Technical Stack

- Frontend: HTML5, CSS3, JavaScript
- Backend: PHP 7.4+
- Database: MySQL 5.7+
- Libraries:
  - Bootstrap 5.3.0
  - Chart.js 3.7.0

## Setup Instructions

1. Set up a local web server (e.g., XAMPP, WAMP)
2. Create a MySQL database named `player_stats`
3. Import the database structure from `database.sql`
4. Update database credentials in `config.php`
5. Place all files in your web server's directory
6. Access the application through your web browser

## Database Structure

The application uses the following tables:
- players: Player information
- matches: Match details
- player_stats: Player performance statistics
- tournaments: Tournament information
- achievements: Player achievements and milestones

## Files Overview

- `index.html`: Main application interface
- `styles.css`: Custom styling
- `script.js`: Frontend JavaScript functionality
- `config.php`: Database configuration
- `get_player_data.php`: API endpoint for player data
- `get_players.php`: API endpoint for player search
- `database.sql`: Database structure

## Requirements

- Web server with PHP 7.4+
- MySQL 5.7+
- Modern web browser with JavaScript enabled
