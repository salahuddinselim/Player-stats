<?php
require_once 'config.php';

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] === 'get_player_data') {
        $playerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($playerId <= 0) {
            echo json_encode(['error' => 'Invalid player ID']);
            exit;
        }

        // Get player info
        $playerQuery = "SELECT * FROM players WHERE player_id = ?";
        $stmt = $conn->prepare($playerQuery);
        $stmt->bind_param("i", $playerId);
        $stmt->execute();
        $playerResult = $stmt->get_result()->fetch_assoc();

        // Get player statistics with performance data
        $statsQuery = "
            SELECT 
                COUNT(DISTINCT m.match_id) as matches_played,
                SUM(ps.goals) as total_goals,
                SUM(ps.assists) as total_assists,
                AVG(
                    CASE 
                        WHEN ps.minutes_played > 0 
                        THEN (ps.goals * 2 + ps.assists) / (ps.minutes_played / 90.0)
                        ELSE 0 
                    END
                ) as rating,
                GROUP_CONCAT(
                    CONCAT(
                        m.date, ',',
                        CASE 
                            WHEN ps.minutes_played > 0 
                            THEN (ps.goals * 2 + ps.assists) / (ps.minutes_played / 90.0)
                            ELSE 0 
                        END
                    )
                    ORDER BY m.date ASC
                ) as performance_trend
            FROM player_stats ps
            JOIN matches m ON ps.match_id = m.match_id
            WHERE ps.player_id = ?
            GROUP BY ps.player_id
        ";
        $stmt = $conn->prepare($statsQuery);
        $stmt->bind_param("i", $playerId);
        $stmt->execute();
        $statsResult = $stmt->get_result()->fetch_assoc();

        // Get recent matches with debugging
        $matchesQuery = "
            SELECT 
                m.date,
                t.name as tournament,
                CASE 
                    WHEN m.home_team = p.team_name THEN m.away_team
                    ELSE m.home_team
                END as opponent,
                CONCAT(
                    CASE 
                        WHEN m.home_team = p.team_name THEN m.home_score
                        ELSE m.away_score
                    END,
                    ' - ',
                    CASE 
                        WHEN m.home_team = p.team_name THEN m.away_score
                        ELSE m.home_score
                    END
                ) as result,
                ps.goals,
                ps.assists,
                ps.minutes_played,
                m.home_score,
                m.away_score,
                m.home_team,
                m.away_team,
                p.team_name
            FROM matches m
            JOIN player_stats ps ON m.match_id = ps.match_id
            JOIN players p ON ps.player_id = p.player_id
            JOIN tournaments t ON m.tournament_id = t.tournament_id
            WHERE ps.player_id = ?
            ORDER BY m.date DESC
            LIMIT 5
        ";
        
        $stmt = $conn->prepare($matchesQuery);
        $stmt->bind_param("i", $playerId);
        $stmt->execute();
        $matchesResult = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Debug information
        $debug = [
            'query' => $matchesQuery,
            'player_id' => $playerId,
            'matches_count' => count($matchesResult),
            'first_match' => !empty($matchesResult) ? $matchesResult[0] : null
        ];

        // Get achievements
        $achievementsQuery = "
            SELECT * FROM achievements 
            WHERE player_id = ?
            ORDER BY date_achieved DESC
        ";
        $stmt = $conn->prepare($achievementsQuery);
        $stmt->bind_param("i", $playerId);
        $stmt->execute();
        $achievementsResult = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $response = [
            'info' => $playerResult,
            'stats' => $statsResult,
            'matches' => $matchesResult,
            'achievements' => $achievementsResult,
            'debug' => $debug
        ];

        echo json_encode($response);
        exit;
    } elseif ($_GET['action'] === 'search_players') {
        $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
        
        if (strlen($searchTerm) < 2) {
            echo json_encode(['error' => 'Search term too short']);
            exit;
        }

        $searchTerm = "%$searchTerm%";
        $query = "SELECT player_id, name, team_name FROM players WHERE name LIKE ? LIMIT 10";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode($result);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Statistics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css">
    <style>
        /* General Styles */
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        /* Player Info Styles */
        .player-info {
            text-align: center;
        }

        .profile-photo {
            width: 200px;
            height: 200px;
            margin: 0 auto 20px;
            overflow: hidden;
            border-radius: 50%;
            border: 5px solid #fff;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            background-color: #f8f9fa;
        }

        .profile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        /* Stats Grid */
        .stats-grid {
            text-align: center;
        }

        .stat-box {
            padding: 15px;
            border-radius: 10px;
            background-color: #fff;
            transition: transform 0.3s ease;
        }

        .stat-box:hover {
            transform: translateY(-5px);
        }

        .stat-box h4 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }

        .stat-box p {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0;
            color: #333;
        }

        /* Search Bar */
        .search-container {
            max-width: 600px;
            margin: 0 auto;
        }

        #playerSearch {
            border-radius: 25px;
            padding: 10px 20px;
            border: 2px solid #ddd;
        }

        /* Charts Container */
        canvas {
            max-width: 100%;
            height: 300px !important;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Achievement Cards */
        .achievement-card {
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
            padding: 10px;
            background: #fff;
            border-radius: 5px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .stat-box {
                margin-bottom: 15px;
            }

            .profile-photo {
                width: 150px;
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Search Bar -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="search-container">
                    <input type="text" id="playerSearch" class="form-control" placeholder="Search for a player...">
                    <div id="searchResults" class="list-group mt-2" style="display: none;"></div>
                </div>
            </div>
        </div>

        <!-- Player Info Section -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="player-info card">
                    <div class="card-body">
                        <div class="profile-photo">
                            <img id="playerPhoto" src="default-avatar.png" alt="Player Photo">
                        </div>
                        <h2 id="playerName" class="card-title">Player Name</h2>
                        <div class="player-details">
                            <p><strong>Age:</strong> <span id="playerAge">25</span></p>
                            <p><strong>Nationality:</strong> <span id="playerNationality">Country</span></p>
                            <p><strong>Team:</strong> <span id="playerTeam">Team Name</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Stats -->
            <div class="col-md-8">
                <div class="stats-container card">
                    <div class="card-body">
                        <h3>Season Statistics</h3>
                        <div class="row stats-grid">
                            <div class="col-md-3 stat-box">
                                <h4>Matches</h4>
                                <p id="matchesPlayed">0</p>
                            </div>
                            <div class="col-md-3 stat-box">
                                <h4>Goals</h4>
                                <p id="goals">0</p>
                            </div>
                            <div class="col-md-3 stat-box">
                                <h4>Assists</h4>
                                <p id="assists">0</p>
                            </div>
                            <div class="col-md-3 stat-box">
                                <h4>Rating</h4>
                                <p id="rating">0.0</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Charts -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card chart-card">
                    <div class="card-body">
                        <h3>Performance Trend</h3>
                        <div class="chart-container">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card chart-card">
                    <div class="card-body">
                        <h3>Goal Contributions</h3>
                        <div class="chart-container">
                            <canvas id="goalContributionsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Matches -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h3>Recent Matches</h3>
                        <div class="table-responsive">
                            <table class="table" id="recentMatches">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Tournament</th>
                                        <th>Opponent</th>
                                        <th>Result</th>
                                        <th>Goals</th>
                                        <th>Assists</th>
                                        <th>Rating</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dynamically populated -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Career Milestones -->
        <div class="row mt-4 mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h3>Career Milestones</h3>
                        <div id="milestones" class="timeline">
                            <!-- Dynamically populated -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script>
        // Initialize charts when the document is ready
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            setupEventListeners();
            loadPlayerData(1); // Load default player
        });

        // Setup event listeners
        function setupEventListeners() {
            const searchInput = document.getElementById('playerSearch');
            const searchResults = document.getElementById('searchResults');
            
            searchInput.addEventListener('input', debounce(handleSearch, 300));
            
            // Hide search results when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchResults.contains(e.target) && e.target !== searchInput) {
                    searchResults.style.display = 'none';
                }
            });
        }

        // Debounce function to limit API calls
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Handle player search
        function handleSearch(event) {
            const searchTerm = event.target.value;
            const searchResults = document.getElementById('searchResults');
            
            if (searchTerm.length >= 2) {
                fetch(`player_stats.php?action=search_players&search=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.json())
                    .then(data => {
                        searchResults.innerHTML = '';
                        data.forEach(player => {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.className = 'list-group-item list-group-item-action';
                            item.textContent = `${player.name} (${player.team_name})`;
                            item.onclick = (e) => {
                                e.preventDefault();
                                loadPlayerData(player.player_id);
                                searchResults.style.display = 'none';
                                document.getElementById('playerSearch').value = player.name;
                            };
                            searchResults.appendChild(item);
                        });
                        searchResults.style.display = data.length > 0 ? 'block' : 'none';
                    })
                    .catch(error => console.error('Error:', error));
            } else {
                searchResults.style.display = 'none';
            }
        }

        // Load player data
        function loadPlayerData(playerId) {
            console.log('Loading player data for ID:', playerId); // Debug log
            
            fetch(`player_stats.php?action=get_player_data&id=${playerId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Received data:', data); // Debug log
                    
                    if (data.debug) {
                        console.log('Debug info:', data.debug); // Log debug information
                    }
                    
                    updatePlayerInfo(data.info);
                    updateStats(data.stats);
                    updateCharts(data.stats);
                    updateRecentMatches(data.matches);
                    updateMilestones(data.achievements);
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Show error in the matches table
                    const tbody = document.querySelector('#recentMatches tbody');
                    tbody.innerHTML = '<tr><td colspan="7">Error loading match data</td></tr>';
                });
        }

        // Update player information
        function updatePlayerInfo(info) {
            if (!info) return;
            
            document.getElementById('playerName').textContent = info.name || 'Player Name';
            document.getElementById('playerAge').textContent = info.age || '-';
            document.getElementById('playerNationality').textContent = info.nationality || '-';
            document.getElementById('playerTeam').textContent = info.team_name || '-';
            
            const playerPhoto = document.getElementById('playerPhoto');
            if (info.profile_photo && info.profile_photo.trim() !== '') {
                playerPhoto.src = info.profile_photo;
                playerPhoto.onerror = function() {
                    this.src = 'default-avatar.png';
                    this.onerror = null;
                };
            } else {
                playerPhoto.src = 'default-avatar.png';
            }
        }

        // Update statistics
        function updateStats(stats) {
            if (!stats) return;
            
            document.getElementById('matchesPlayed').textContent = stats.matches_played || '0';
            document.getElementById('goals').textContent = stats.total_goals || '0';
            document.getElementById('assists').textContent = stats.total_assists || '0';
            document.getElementById('rating').textContent = parseFloat(stats.rating || 0).toFixed(1);
        }

        // Initialize charts
        function initializeCharts() {
            // Performance Chart
            const perfCtx = document.getElementById('performanceChart').getContext('2d');
            window.performanceChart = new Chart(perfCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Performance Rating',
                        data: [],
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 5,
                            max: 10
                        }
                    }
                }
            });

            // Goal Contributions Chart
            const goalCtx = document.getElementById('goalContributionsChart').getContext('2d');
            window.goalContributionsChart = new Chart(goalCtx, {
                type: 'bar',
                data: {
                    labels: ['Goals', 'Assists'],
                    datasets: [{
                        label: 'Season Contributions',
                        data: [0, 0],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.5)',
                            'rgba(54, 162, 235, 0.5)'
                        ],
                        borderColor: [
                            'rgb(255, 99, 132)',
                            'rgb(54, 162, 235)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Update charts with new data
        function updateCharts(stats) {
            if (!stats) return;

            // Update goal contributions chart
            if (window.goalContributionsChart) {
                window.goalContributionsChart.data.datasets[0].data = [
                    parseInt(stats.total_goals || 0),
                    parseInt(stats.total_assists || 0)
                ];
                window.goalContributionsChart.update();
            }

            // Update performance trend chart
            if (window.performanceChart && stats.performance_trend) {
                const performanceData = stats.performance_trend.split(',');
                const dates = [];
                const ratings = [];
                
                for (let i = 0; i < performanceData.length; i += 2) {
                    if (performanceData[i] && performanceData[i+1]) {
                        dates.push(formatDate(performanceData[i]));
                        ratings.push(parseFloat(performanceData[i + 1]));
                    }
                }
                
                window.performanceChart.data.labels = dates;
                window.performanceChart.data.datasets[0].data = ratings;
                window.performanceChart.update();
            }
        }

        // Update recent matches table
        function updateRecentMatches(matches) {
            const tbody = document.querySelector('#recentMatches tbody');
            tbody.innerHTML = '';
            
            if (!matches || matches.length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="7" class="text-center">No matches found</td>';
                tbody.appendChild(row);
                return;
            }
            
            matches.forEach(match => {
                if (!match) return;
                
                const rating = match.minutes_played > 0 
                    ? ((match.goals * 2 + match.assists) / (match.minutes_played / 90.0)).toFixed(1)
                    : '0.0';
                    
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${formatDate(match.date)}</td>
                    <td>${match.tournament || '-'}</td>
                    <td>${match.opponent || '-'}</td>
                    <td>${match.result || '-'}</td>
                    <td>${match.goals || '0'}</td>
                    <td>${match.assists || '0'}</td>
                    <td>${rating}</td>
                `;
                tbody.appendChild(row);
            });
        }

        // Update career milestones
        function updateMilestones(milestones) {
            const container = document.getElementById('milestones');
            container.innerHTML = '';
            
            milestones.forEach(milestone => {
                const card = document.createElement('div');
                card.className = 'achievement-card';
                card.innerHTML = `
                    <h4>${milestone.title}</h4>
                    <p>${milestone.description}</p>
                    <small>${formatDate(milestone.date_achieved)}</small>
                `;
                container.appendChild(card);
            });
        }

        // Helper function to format dates
        function formatDate(dateString) {
            if (!dateString) return '-';
            try {
                const options = { year: 'numeric', month: 'short', day: 'numeric' };
                return new Date(dateString).toLocaleDateString(undefined, options);
            } catch (e) {
                console.error('Error formatting date:', e);
                return dateString;
            }
        }
    </script>
</body>
</html>
