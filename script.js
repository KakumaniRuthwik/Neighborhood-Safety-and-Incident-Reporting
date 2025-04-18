document.addEventListener('DOMContentLoaded', function() {
    // Initialize map if on dashboard page
    if (document.getElementById('incident-map')) {
        initMap();
        loadIncidents();
    }

    // Set up incident form submission if on report page
    const incidentForm = document.getElementById('incident-form');
    if (incidentForm) {
        incidentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (validateForm(this)) {
                submitIncidentReport(this);
            }
        });
    }

    // Set up filter buttons on dashboard
    const filterButtons = document.querySelectorAll('.filter-btn');
    if (filterButtons.length > 0) {
        filterButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                filterButtons.forEach(b => b.classList.remove('active', 'bg-blue-600', 'text-white'));
                filterButtons.forEach(b => b.classList.add('bg-gray-200'));
                this.classList.add('active', 'bg-blue-600', 'text-white');
                this.classList.remove('bg-gray-200');
                const filter = this.getAttribute('data-filter');
                filterIncidents(filter);
            });
        });
        const allFilter = Array.from(filterButtons).find(btn => btn.getAttribute('data-filter') === 'all');
        if (allFilter) {
            allFilter.classList.add('active', 'bg-blue-600', 'text-white');
            allFilter.classList.remove('bg-gray-200');
        }
    }

    // Set up time filter on dashboard
    const timeFilter = document.getElementById('time-filter');
    if (timeFilter) {
        timeFilter.addEventListener('change', function() {
            const activeBtn = document.querySelector('.filter-btn.active');
            const activeFilter = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';
            filterIncidents(activeFilter, this.value);
        });
    }

    // Load more button functionality
    const loadMoreBtn = document.getElementById('load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            loadMoreIncidents();
        });
    }
});

// Initialize map
function initMap() {
    if (typeof L === 'undefined') {
        console.error('Leaflet library is not loaded');
        return;
    }
    const map = L.map('incident-map').setView([20.5937, 78.9629], 5); // Center on India with a wider zoom
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    window.incidentMap = map;
}

// Validate form
function validateForm(form) {
    const photo = form.querySelector('#incident-photo');
    const dateInput = form.querySelector('#incident-date');
    const today = new Date().toISOString().split('T')[0];

    if (!form.querySelector('#incident-type').value) {
        showFormError('Please select an incident type.');
        return false;
    }
    if (!form.querySelector('#incident-title').value.trim()) {
        showFormError('Incident title is required.');
        return false;
    }
    if (!form.querySelector('#incident-description').value.trim()) {
        showFormError('Incident description is required.');
        return false;
    }
    if (!form.querySelector('#incident-location').value.trim()) {
        showFormError('Incident location is required.');
        return false;
    }
    if (!form.querySelector('#incident-area').value.trim()) {
        showFormError('Area/neighborhood is required.');
        return false;
    }
    if (!dateInput.value) {
        showFormError('Incident date is required.');
        return false;
    }
    if (dateInput.value > today) {
        showFormError('Incident date cannot be in the future.');
        return false;
    }
    if (!form.querySelector('#incident-time').value) {
        showFormError('Incident time is required.');
        return false;
    }
    if (!form.querySelector('#consent').checked) {
        showFormError('You must confirm the report is truthful.');
        return false;
    }
    if (photo.files.length > 0) {
        const file = photo.files[0];
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            showFormError('Photo size exceeds 5MB limit.');
            return false;
        }
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            showFormError('Photo must be JPG, PNG, or GIF.');
            return false;
        }
    }
    return true;
}





// Mock incidents
function getMockIncidents() {
    const today = new Date();
    const offsetDate = days => {
        const d = new Date(today);
        d.setDate(d.getDate() - days);
        return d.toISOString().split('T')[0];
    };
    return [
        { id: 1, type: 'theft', title: 'Scooter stolen', description: 'Scooter taken from market.', location: 'Podalakur Market', area: 'podalakur', date: offsetDate(2), time: '14:30', lat: 14.3667, lng: 79.6167, photo_path: null, created_at: `${offsetDate(2)} 14:45:00` },
        { id: 2, type: 'suspicious', title: 'Suspicious person', description: 'Person loitering near shop.', location: 'Nellore Bazar', area: 'nellore', date: offsetDate(1), time: '23:15', lat: 14.4426, lng: 79.9865, photo_path: null, created_at: `${offsetDate(1)} 23:30:00` },
        { id: 3, type: 'hazard', title: 'Pothole hazard', description: 'Pothole on NH-16.', location: 'NH-16, Podalakur', area: 'podalakur', date: offsetDate(0), time: '08:00', lat: 14.3650, lng: 79.6150, photo_path: null, created_at: `${offsetDate(0)} 08:30:00` }
    ];
}

// Render incident
function renderIncident(incident, typeColors) {
    const color = typeColors[incident.type] || 'gray';
    const incidentDate = new Date(incident.created_at || `${incident.date} ${incident.time}`);
    const timeAgo = getTimeAgo(incidentDate);
    const typeDisplay = incident.type.charAt(0).toUpperCase() + incident.type.slice(1);
    const areaDisplay = incident.area.charAt(0).toUpperCase() + incident.area.slice(1);

    const incidentElement = document.createElement('div');
    incidentElement.className = `incident-item bg-white p-6 rounded-lg shadow-md`;
    incidentElement.setAttribute('data-incident-type', incident.type);
    incidentElement.setAttribute('role', 'listitem');
    incidentElement.innerHTML = `
        <span class="inline-block bg-${color}-100 text-${color}-800 px-3 py-1 rounded-full text-sm font-semibold mb-3">${typeDisplay}</span>
        <h3 class="text-xl font-semibold mb-2">${incident.title}</h3>
        <p class="text-gray-600 mb-4">${incident.description}</p>
        <div class="flex justify-between text-sm text-gray-500">
            <span>${areaDisplay}</span>
            <span>${timeAgo}</span>
        </div>`;
    return incidentElement;
}

// Display incidents
function displayIncidents(incidents, clearList = false) {
    const incidentList = document.getElementById('incident-list');
    if (!incidentList) return;

    const typeColors = {
        theft: 'red', vandalism: 'purple', suspicious: 'yellow',
        assault: 'orange', hazard: 'blue', noise: 'green', other: 'gray', protest: 'orange'
    };

    if (clearList) {
        incidentList.innerHTML = '';
    }

    incidents.forEach(incident => {
        const incidentElement = renderIncident(incident, typeColors);
        if (incidentElement) {
            incidentList.appendChild(incidentElement);
        }
    });
}

// Update stats
function updateStats(incidents) {
    const totalIncidents = document.getElementById('total-incidents');
    if (totalIncidents) totalIncidents.textContent = incidents.length;

    const typeCounts = { theft: 0, suspicious: 0, vandalism: 0, hazard: 0, other: 0 };
    incidents.forEach(incident => {
        const type = typeCounts.hasOwnProperty(incident.type) ? incident.type : 'other';
        typeCounts[type]++;
    });

    Object.keys(typeCounts).forEach(type => {
        const countEl = document.getElementById(`${type}-count`);
        const barEl = document.getElementById(`${type}-bar`);
        if (countEl && barEl) {
            countEl.textContent = typeCounts[type];
            const percentage = incidents.length ? (typeCounts[type] / incidents.length) * 100 : 0;
            barEl.style.width = `${percentage}%`;
        }
    });
}

// Add incidents to map
function addIncidentsToMap(incidents) {
    if (!window.incidentMap) return;
    if (window.markers) window.markers.forEach(marker => window.incidentMap.removeLayer(marker));
    window.markers = [];

    const bounds = []; // Create an empty array to store marker coordinates

    incidents.forEach(incident => {
        if (incident.lat && incident.lng) {
            const latLng = [incident.lat, incident.lng];
            const marker = L.marker(latLng).addTo(window.incidentMap);
            marker.bindPopup(`
                <div class="incident-popup">
                    <h3 class="font-bold">${incident.title}</h3>
                    <p>${incident.description}</p>
                    <div class="text-sm text-gray-500 mt-2">
                        <div>${incident.location}</div>
                        <div>${formatDate(incident.date)} at ${formatTime(incident.time)}</div>
                    </div>
                </div>
            `);
            window.markers.push(marker);
            bounds.push(latLng); // Add the marker's coordinates to the bounds array
        }
    });

    if (bounds.length > 0) {
        window.incidentMap.fitBounds(bounds, { padding: [50, 50] }); // Adjust map to fit all markers with some padding
    } else {
        // Only set a default view if NO incidents were loaded
        window.incidentMap.setView([20.5937, 78.9629], 5); // Default to India view if no incidents
    }
}

// Filter incidents
function filterIncidents(type, timeRange) {
    const loading = document.getElementById('incident-loading');
    const error = document.getElementById('incident-error');
    const noMore = document.getElementById('no-more-incidents');
    if (loading) loading.classList.remove('hidden');
    if (error) error.classList.add('hidden');
    if (noMore) noMore.classList.add('hidden');

    currentPage = 1; // Reset page for new filter
    fetch(`get_incidents.php?type=${type}&time=${timeRange || 'week'}&page=1`)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error: ${response.status}`);
            return response.json();
        })
        .catch(err => {
            console.error('Error fetching filtered incidents:', err);
            if (error) {
                error.classList.remove('hidden');
                error.textContent = 'Failed to load incidents. Showing mock data.';
            }
            return { incidents: filterMockIncidents(getMockIncidents(), type, timeRange), has_more: false };
        })
        .then(data => {
            const sanitizedData = data.incidents.map(incident => ({
                ...incident,
                type: incident.type || 'other',
                area: incident.area || 'Unknown',
                title: incident.title || 'Untitled',
                description: incident.description || 'No description',
                date: incident.date || new Date().toISOString().split('T')[0],
                time: incident.time || '00:00',
                location: incident.location || 'Unknown location',
                lat: parseFloat(incident.lat), // Ensure these are parsed, but no default here
                lng: parseFloat(incident.lng), // Ensure these are parsed, but no default here
                photo_path: incident.photo_path || null,
                created_at: incident.created_at || new Date().toISOString()
            }));
            displayIncidents(sanitizedData, true);
            if (window.incidentMap) addIncidentsToMap(sanitizedData);
            updateStats(sanitizedData);
            const loadMoreBtn = document.getElementById('load-more-btn');
            if (loadMoreBtn) {
                loadMoreBtn.disabled = !data.has_more;
                loadMoreBtn.textContent = data.has_more ? 'Load More' : 'No more incidents';
            }
            if (loading) loading.classList.add('hidden');
        });
}


// Filter mock incidents
function filterMockIncidents(incidents, type, timeRange) {
    let filtered = incidents;
    if (type && type !== 'all') {
        filtered = filtered.filter(incident => incident.type === type);
    }
    if (timeRange) {
        const now = new Date();
        let cutoff = new Date();
        switch (timeRange) {
            case '24h': cutoff.setDate(now.getDate() - 1); break;
            case 'week': cutoff.setDate(now.getDate() - 7); break;
            case 'month': cutoff.setMonth(now.getMonth() - 1); break;
            default: cutoff = new Date(0);
        }
        filtered = filtered.filter(incident => {
            const incidentDate = new Date(incident.created_at);
            return incidentDate >= cutoff;
        });
    }
    return filtered;
}

// Submit incident report
function submitIncidentReport(form) {
    const loading = document.getElementById('form-loading');
    const error = document.getElementById('form-error');
    const success = document.getElementById('form-success');
    const submitBtn = form.querySelector('button[type="submit"]');

    if (loading) loading.classList.remove('hidden');
    if (error) {
        error.classList.add('hidden');
        error.textContent = '';
    }
    if (success) {
        success.classList.add('hidden');
        success.textContent = '';
    }
    if (submitBtn) submitBtn.disabled = true;

    function submitFormWithLocation(lat, lng) {
        const formData = new FormData(form);
        if (lat !== null && lng !== null) {
            formData.append('latitude', lat);
            formData.append('longitude', lng);
        }

        console.log('FormData entries (with location):');
        for (let [key, value] of formData.entries()) {
            console.log(`${key}: ${value instanceof File ? value.name : value}`);
        }

        fetch('report_handler.php', {
            method: 'POST',
            body: formData,
            signal: AbortSignal.timeout(10000) // 10s timeout
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) throw new Error(`HTTP error: ${response.status}`);
            return response.json();
        })
        .then(data => {
            console.log('Server response:', data);
            if (data.success) {
                if (success) {
                    success.classList.remove('hidden');
                    success.textContent = 'Report submitted successfully! Redirecting to dashboard...';
                    setTimeout(() => window.location.href = 'dashboard.html', 2000);
                }
                form.reset();
            } else {
                if (error) {
                    error.classList.remove('hidden');
                    error.textContent = data.message || 'Failed to submit report.';
                }
            }
        })
        .catch(err => {
            console.error('Error submitting report:', err);
            if (error) {
                error.classList.remove('hidden');
                if (err.name === 'TimeoutError' || err.message.includes('timeout')) {
                    error.textContent = 'Request timed out. Please try again.';
                } else if (err.message.includes('Failed to fetch')) {
                    error.textContent = 'Network error. Please check your connection.';
                } else {
                    error.textContent = err.message || 'An unknown error occurred.';
                }
            }
        })
        .finally(() => {
            if (loading) loading.classList.add('hidden');
            if (submitBtn) submitBtn.disabled = false;
        });
    }

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            position => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                submitFormWithLocation(lat, lng);
            },
            error => {
                console.error('Error getting geolocation:', error);
                // If geolocation fails, submit without location
                submitFormWithLocation(null, null);
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    } else {
        // Geolocation is not supported
        submitFormWithLocation(null, null);
    }
}

// Show form error
function showFormError(message) {
    const error = document.getElementById('form-error');
    if (error) {
        error.textContent = message;
        error.classList.remove('hidden');
    }
}

let currentPage = 1;
const initialPerPage = -1; // Use -1 to indicate "load all" on the first page
const subsequentPerPage = 5; // Use a smaller number for "Load More"

// Load incidents from server
function loadIncidents() {
    const loading = document.getElementById('incident-loading');
    const error = document.getElementById('incident-error');
    const noMore = document.getElementById('no-more-incidents');
    const loadMoreBtn = document.getElementById('load-more-btn');

    if (loading) loading.classList.remove('hidden');
    if (error) error.classList.add('hidden');
    if (noMore) noMore.classList.add('hidden');
    if (loadMoreBtn) loadMoreBtn.classList.add('hidden'); // Hide initially

    const currentPerPage = currentPage === 1 ? initialPerPage : subsequentPerPage;

    fetch(`get_incidents.php?page=${currentPage}&per_page=${currentPerPage}`)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error: ${response.status}`);
            return response.json();
        })
        .catch(err => {
            console.error('Error fetching incidents:', err);
            if (error) {
                error.classList.remove('hidden');
                error.textContent = 'Failed to load incidents. Showing mock data.';
            }
            return { incidents: getMockIncidents(), has_more: false };
        })
        .then(data => {
            const sanitizedData = data.incidents.map(incident => ({
                ...incident,
                type: incident.type || 'other',
                area: incident.area || 'Unknown',
                title: incident.title || 'Untitled',
                description: incident.description || 'No description',
                date: incident.date || new Date().toISOString().split('T')[0],
                time: incident.time || '00:00',
                location: incident.location || 'Unknown location',
                lat: parseFloat(incident.lat),
                lng: parseFloat(incident.lng),
                photo_path: incident.photo_path || null,
                created_at: incident.created_at || new Date().toISOString()
            }));
            displayIncidents(sanitizedData, currentPage === 1); // Clear list only on initial load
            if (window.incidentMap) addIncidentsToMap(sanitizedData);
            updateStats(sanitizedData);

            if (currentPage === 1) {
                if (data.has_more) {
                    if (loadMoreBtn) {
                        loadMoreBtn.classList.remove('hidden');
                        loadMoreBtn.disabled = false;
                        loadMoreBtn.textContent = 'Load More';
                    }
                    if (noMore) noMore.classList.add('hidden');
                } else {
                    if (loadMoreBtn) loadMoreBtn.classList.add('hidden');
                    if (noMore) {
                        noMore.classList.remove('hidden');
                        noMore.textContent = 'No more incidents.';
                    }
                }
            } else {
                if (data.has_more) {
                    if (loadMoreBtn) {
                        loadMoreBtn.disabled = false;
                        loadMoreBtn.textContent = 'Load More';
                    }
                    if (noMore) noMore.classList.add('hidden');
                } else {
                    if (loadMoreBtn) {
                        loadMoreBtn.disabled = true;
                        loadMoreBtn.textContent = 'No more incidents';
                    }
                    if (noMore) noMore.classList.remove('hidden');
                    noMore.textContent = 'No more incidents to load.';
                }
            }

            if (loading) loading.classList.add('hidden');
        });
}

function loadMoreIncidents() {
    const activeBtn = document.querySelector('.filter-btn.active');
    const activeFilter = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';
    const timeFilter = document.getElementById('time-filter')?.value || 'week';
    const loading = document.getElementById('incident-loading');
    const error = document.getElementById('incident-error');
    const noMore = document.getElementById('no-more-incidents');
    const loadMoreBtn = document.getElementById('load-more-btn');

    if (loading) loading.classList.remove('hidden');
    if (error) error.classList.add('hidden');
    if (noMore) noMore.classList.add('hidden');
    if (loadMoreBtn) loadMoreBtn.disabled = true;
    loadMoreBtn.textContent = 'Loading all incidents...';

    fetch(`get_incidents.php?type=${activeFilter}&time=${timeFilter}&page=${currentPage + 1}&per_page=${loadAllRemaining}`)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error: ${response.status}`);
            return response.json();
        })
        .catch(err => {
            console.error('Error fetching more incidents:', err);
            if (error) {
                error.classList.remove('hidden');
                error.textContent = 'Failed to load more incidents.';
            }
            return { incidents: [], has_more: false };
        })
        .then(data => {
            console.log('Fetched all remaining data:', data);
            const sanitizedData = data.incidents.map(incident => ({
                ...incident,
                type: incident.type || 'other',
                area: incident.area || 'Unknown',
                title: incident.title || 'Untitled',
                description: incident.description || 'No description',
                date: incident.date || new Date().toISOString().split('T')[0],
                time: incident.time || '00:00',
                location: incident.location || 'Unknown location',
                lat: parseFloat(incident.lat),
                lng: parseFloat(incident.lng),
                photo_path: incident.photo_path || null,
                created_at: incident.created_at || new Date().toISOString()
            }));

            displayIncidents(sanitizedData, false); // Append to the existing list
            if (window.incidentMap) addIncidentsToMap(sanitizedData);
            updateStats(document.querySelectorAll('.incident-item')); // Update stats based on all loaded items

            if (noMore) {
                noMore.classList.remove('hidden');
                noMore.textContent = 'All incidents loaded.';
            }
            if (loadMoreBtn) {
                loadMoreBtn.classList.add('hidden');
                loadMoreBtn.disabled = true;
                loadMoreBtn.textContent = 'All incidents loaded';
            }

            if (loading) loading.classList.add('hidden');
            currentPage = data.total > 0 ? Math.ceil(data.total / initialPerPage) : 1; // Update currentPage to reflect all loaded
        });
}


// Time ago
function getTimeAgo(date) {
    if (isNaN(date)) return 'Unknown time';
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    if (diffInSeconds < 60) return `${diffInSeconds} seconds ago`;
    const diffInMinutes = Math.floor(diffInSeconds / 60);
    if (diffInMinutes < 60) return `${diffInMinutes} minute${diffInMinutes !== 1 ? 's' : ''} ago`;
    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) return `${diffInHours} hour${diffInHours !== 1 ? 's' : ''} ago`;
    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays < 30) return `${diffInDays} day${diffInDays !== 1 ? 's' : ''} ago`;
    const diffInMonths = Math.floor(diffInDays / 30);
    return `${diffInMonths} month${diffInMonths !== 1 ? 's' : ''} ago`;
}

// Format date
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    try {
        return new Date(dateString).toLocaleDateString(undefined, options);
    } catch (e) {
        return 'Unknown date';
    }
}

// Format time
function formatTime(timeString) {
    const options = { hour: 'numeric', minute: 'numeric', hour12: true };
    try {
        return new Date(`2000-01-01T${timeString}`).toLocaleTimeString(undefined, options);
    } catch (e) {
        return 'Unknown time';
    }
}