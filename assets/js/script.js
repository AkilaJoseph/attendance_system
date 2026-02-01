/**
 * Attendance System - Client-Side JavaScript
 * Handles form validation, interactions, and dynamic UI updates
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initLoginValidation();
    initAttendanceForm();
    initPercentageCircles();
    initConfirmDialogs();
    initNotifications();
});

/**
 * Login Form Validation
 */
function initLoginValidation() {
    const loginForm = document.getElementById('loginForm');

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            let isValid = true;

            // Clear previous errors
            clearErrors();

            // Validate email
            const email = document.getElementById('email');
            if (email && !validateEmail(email.value)) {
                showError(email, 'Please enter a valid email address');
                isValid = false;
            }

            // Validate password
            const password = document.getElementById('password');
            if (password && password.value.length < 1) {
                showError(password, 'Password is required');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }
}

/**
 * Validate email format
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Show error message for input field
 */
function showError(input, message) {
    input.classList.add('error');

    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;

    input.parentNode.appendChild(errorDiv);
}

/**
 * Clear all error messages
 */
function clearErrors() {
    document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
    document.querySelectorAll('.error-message').forEach(el => el.remove());
}

/**
 * Attendance Form Handling
 */
function initAttendanceForm() {
    const attendanceForm = document.getElementById('attendanceForm');

    if (attendanceForm) {
        // Select all present/absent buttons
        const selectAllPresent = document.getElementById('selectAllPresent');
        const selectAllAbsent = document.getElementById('selectAllAbsent');

        if (selectAllPresent) {
            selectAllPresent.addEventListener('click', function() {
                document.querySelectorAll('input[value="present"]').forEach(radio => {
                    radio.checked = true;
                });
            });
        }

        if (selectAllAbsent) {
            selectAllAbsent.addEventListener('click', function() {
                document.querySelectorAll('input[value="absent"]').forEach(radio => {
                    radio.checked = true;
                });
            });
        }

        // Form submission confirmation
        attendanceForm.addEventListener('submit', function(e) {
            const date = document.querySelector('input[name="date"]');

            if (!date || !date.value) {
                e.preventDefault();
                alert('Please select a date for attendance');
                return;
            }

            // Count present and absent
            const presentCount = document.querySelectorAll('input[value="present"]:checked').length;
            const absentCount = document.querySelectorAll('input[value="absent"]:checked').length;

            const confirmMsg = `You are marking:\n- Present: ${presentCount} students\n- Absent: ${absentCount} students\n\nDo you want to save this attendance?`;

            if (!confirm(confirmMsg)) {
                e.preventDefault();
            }
        });
    }
}

/**
 * Animate percentage circles
 */
function initPercentageCircles() {
    const circles = document.querySelectorAll('.percentage-circle');

    circles.forEach(circle => {
        const percentage = parseInt(circle.getAttribute('data-percentage')) || 0;
        circle.style.setProperty('--percent', percentage + '%');

        // Animate the number
        const span = circle.querySelector('span');
        if (span) {
            animateNumber(span, 0, percentage, 1000);
        }
    });
}

/**
 * Animate a number from start to end
 */
function animateNumber(element, start, end, duration) {
    const startTime = performance.now();

    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        const current = Math.round(start + (end - start) * progress);
        element.textContent = current + '%';

        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }

    requestAnimationFrame(update);
}

/**
 * Initialize confirmation dialogs for destructive actions
 */
function initConfirmDialogs() {
    document.querySelectorAll('[data-confirm]').forEach(element => {
        element.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm');
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Show notification toast
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;

    document.body.appendChild(toast);

    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);

    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Toggle dark/light mode
 */
function toggleTheme() {
    document.body.classList.toggle('light-mode');
    const isLight = document.body.classList.contains('light-mode');
    localStorage.setItem('theme', isLight ? 'light' : 'dark');
}

// Load saved theme preference
(function loadTheme() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'light') {
        document.body.classList.add('light-mode');
    }
})();

/**
 * Search/filter table rows
 */
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);

    if (!input || !table) return;

    input.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}

/**
 * Format date to locale string
 */
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}

/**
 * ========================================
 * NOTIFICATION SYSTEM
 * ========================================
 */

let notificationSound = null;
let lastNotifiedIds = new Set();

/**
 * Initialize notification system
 */
function initNotifications() {
    const bell = document.getElementById('notificationBell');
    if (!bell) return; // Not logged in

    // Request notification permission
    requestNotificationPermission();

    // Initialize Web Audio API for notification sound
    initNotificationSound();

    // Check for reminders immediately
    checkReminders();

    // Poll for reminders every 60 seconds
    setInterval(checkReminders, 60000);

    // Close panel when clicking outside
    document.addEventListener('click', function(e) {
        const panel = document.getElementById('notificationPanel');
        const bell = document.getElementById('notificationBell');
        if (panel && !panel.contains(e.target) && !bell.contains(e.target)) {
            panel.classList.remove('show');
        }
    });
}

/**
 * Request browser notification permission
 */
function requestNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
}

/**
 * Check for active reminders via API
 */
function checkReminders() {
    fetch('/attendance_system/api/get_reminders.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateBadgeCount(data.count);
                updateNotificationList(data.reminders);

                // Show browser notifications for new reminders
                data.reminders.forEach(reminder => {
                    if (!lastNotifiedIds.has(reminder.reminder_id)) {
                        showBrowserNotification(reminder);
                        lastNotifiedIds.add(reminder.reminder_id);
                    }
                });
            }
        })
        .catch(error => console.log('Error checking reminders:', error));
}

/**
 * Update the badge count
 */
function updateBadgeCount(count) {
    const badge = document.getElementById('reminderBadge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

/**
 * Update the notification list in the panel
 */
function updateNotificationList(reminders) {
    const list = document.getElementById('notificationList');
    if (!list) return;

    if (reminders.length === 0) {
        list.innerHTML = '<div class="notification-empty">No pending reminders</div>';
        return;
    }

    list.innerHTML = reminders.map(r => `
        <div class="notification-item" data-id="${r.reminder_id}">
            <div class="notification-icon ${r.event_type}">${getEventIcon(r.event_type)}</div>
            <div class="notification-content">
                <div class="notification-title">${escapeHtml(r.title)}</div>
                <div class="notification-meta">
                    <span class="type">${r.event_type_name}</span>
                    <span class="time">${r.time_until}</span>
                </div>
                <div class="notification-date">${r.formatted_date}${r.formatted_time ? ' at ' + r.formatted_time : ''}</div>
            </div>
            <button class="notification-dismiss" onclick="dismissReminder(${r.reminder_id}, event)">×</button>
        </div>
    `).join('');
}

/**
 * Get icon for event type
 */
function getEventIcon(type) {
    const icons = {
        'class': '📚',
        'ue': '📝',
        'ca': '✍️',
        'assignment': '📋',
        'other': '📌'
    };
    return icons[type] || '📌';
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Toggle notification panel visibility
 */
function toggleNotificationPanel() {
    const panel = document.getElementById('notificationPanel');
    if (panel) {
        panel.classList.toggle('show');
    }
}

/**
 * Show browser notification with sound
 */
function showBrowserNotification(reminder) {
    // Play sound
    playNotificationSound();

    // Show browser notification if permitted
    if ('Notification' in window && Notification.permission === 'granted') {
        const notification = new Notification(reminder.title, {
            body: `${reminder.event_type_name} - ${reminder.time_until}\n${reminder.formatted_date}`,
            icon: '/attendance_system/assets/images/icon.png',
            tag: 'reminder-' + reminder.reminder_id,
            requireInteraction: true
        });

        notification.onclick = function() {
            window.focus();
            toggleNotificationPanel();
            notification.close();
        };

        // Auto close after 10 seconds
        setTimeout(() => notification.close(), 10000);
    }

    // Also show toast notification
    showToast(`⏰ ${reminder.title} - ${reminder.time_until}`, 'warning');
}

/**
 * Initialize notification sound using Web Audio API
 */
let audioContext = null;

function initNotificationSound() {
    try {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
    } catch (e) {
        console.log('Web Audio API not supported');
    }
}

/**
 * Play notification sound using Web Audio API
 */
function playNotificationSound() {
    if (!audioContext) return;

    try {
        // Resume audio context if suspended (browser autoplay policy)
        if (audioContext.state === 'suspended') {
            audioContext.resume();
        }

        // Create a simple notification beep
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.frequency.value = 800; // Frequency in Hz
        oscillator.type = 'sine';

        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);

        // Play a second beep
        setTimeout(() => {
            const osc2 = audioContext.createOscillator();
            const gain2 = audioContext.createGain();
            osc2.connect(gain2);
            gain2.connect(audioContext.destination);
            osc2.frequency.value = 1000;
            osc2.type = 'sine';
            gain2.gain.setValueAtTime(0.3, audioContext.currentTime);
            gain2.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
            osc2.start(audioContext.currentTime);
            osc2.stop(audioContext.currentTime + 0.3);
        }, 150);

    } catch (e) {
        console.log('Could not play notification sound');
    }
}

/**
 * Dismiss a single reminder
 */
function dismissReminder(reminderId, event) {
    event.stopPropagation();

    fetch('/attendance_system/actions/mark_reminder_seen.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'reminder_id=' + reminderId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove from UI
            const item = document.querySelector(`.notification-item[data-id="${reminderId}"]`);
            if (item) {
                item.style.animation = 'slideOut 0.3s ease forwards';
                setTimeout(() => {
                    item.remove();
                    checkReminders(); // Refresh count
                }, 300);
            }
        }
    });
}

/**
 * Mark all reminders as seen
 */
function markAllSeen() {
    fetch('/attendance_system/actions/mark_reminder_seen.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'mark_all=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            lastNotifiedIds.clear();
            checkReminders();
        }
    });
}
