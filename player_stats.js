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
