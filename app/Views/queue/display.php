<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue Display Monitor - Brgy. Sinalhan Health Center</title>
    <!-- Local CSS Vendor Files -->
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/index.css') ?>">
    <style>
        body.queue-display-body {
            background-color: #0A3D40;
            color: #ffffff;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .display-header {
            background-color: rgba(9, 91, 94, 0.4);
            border-bottom: 2px solid var(--color-primary-light);
            padding: 15px 30px;
        }

        .display-content {
            flex-grow: 1;
            padding: 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .now-serving-card {
            background: linear-gradient(145deg, #0d7377 0%, #095b5e 100%);
            border: 3px solid var(--color-primary-light);
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
        }

        .now-serving-label {
            font-size: 2.2rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #14a3a8;
        }

        .now-serving-number {
            font-size: 11rem;
            font-weight: 900;
            line-height: 1;
            color: #ffffff;
            font-family: monospace, sans-serif;
            text-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
            margin-top: 15px;
        }

        .queue-column-card {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 24px;
            height: 100%;
        }

        .queue-column-title {
            font-size: 1.5rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 12px;
            margin-bottom: 20px;
            color: #14a3a8;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .queue-number-list {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .queue-number-badge {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 10px 20px;
            font-size: 1.8rem;
            font-weight: 700;
            font-family: monospace, sans-serif;
            color: #ffffff;
            transition: all 0.3s ease;
        }

        .queue-number-badge.first-called {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #2d3436;
            animation: pulse-border 1.5s infinite;
        }

        @keyframes pulse-border {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
        }

        .display-footer {
            padding: 15px 30px;
            background-color: rgba(0, 0, 0, 0.2);
            font-size: 0.9rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body class="queue-display-body">

    <!-- Header Section -->
    <header class="display-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-heart-pulse-fill text-info fs-2"></i>
            <div>
                <h1 class="h4 fw-bold mb-0 text-white">Barangay Sinalhan Health Center</h1>
                <span class="text-info small fw-semibold tracking-wider text-uppercase" style="font-size: 0.75rem;">Public Waiting Room Monitor</span>
            </div>
        </div>
        <div class="text-end">
            <h5 class="h6 mb-0 text-white" id="liveTime">Loading time...</h5>
            <span class="text-muted small" id="liveDate"><?= date('F d, Y') ?></span>
        </div>
    </header>

    <!-- Main Content Grid -->
    <main class="display-content container-fluid">
        <!-- 1. Now Serving Jumbotron -->
        <div class="now-serving-card">
            <div class="now-serving-label"><i class="bi bi-megaphone-fill me-2"></i>Now Serving</div>
            <div class="now-serving-number" id="nowServingNumber">000</div>
        </div>

        <!-- 2. Waiting and Recently Called -->
        <div class="row g-4 flex-grow-1" style="min-height: 250px;">
            <!-- Waiting List -->
            <div class="col-12 col-md-6">
                <div class="queue-column-card">
                    <h3 class="queue-column-title">
                        <i class="bi bi-hourglass-split"></i>
                        <span>Waiting Queue</span>
                    </h3>
                    <div class="queue-number-list" id="waitingListContainer">
                        <span class="text-muted small">No patients in waiting line.</span>
                    </div>
                </div>
            </div>

            <!-- Recently Called List -->
            <div class="col-12 col-md-6">
                <div class="queue-column-card">
                    <h3 class="queue-column-title">
                        <i class="bi bi-clock-history"></i>
                        <span>Recently Called</span>
                    </h3>
                    <div class="queue-number-list" id="calledListContainer">
                        <span class="text-muted small">No recently called numbers.</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="display-footer">
        <span>Please wait for your number to be called. Thank you for your cooperation &bull; Sinalhan PMS</span>
    </footer>

    <!-- Local Vendor Scripts -->
    <script src="<?= asset('vendor/jquery/jquery.min.js') ?>"></script>
    <script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let currentServing = '000';
        let initialLoad = true;

        // 1. Live Clock
        function updateClock() {
            const now = new Date();
            let hours = now.getHours();
            let minutes = now.getMinutes();
            let seconds = now.getSeconds();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            
            hours = hours % 12;
            hours = hours ? hours : 12; // the hour '0' should be '12'
            minutes = minutes < 10 ? '0' + minutes : minutes;
            seconds = seconds < 10 ? '0' + seconds : seconds;
            
            const timeStr = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
            document.getElementById('liveTime').textContent = timeStr;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // 2. Beep Notification audio chimes (Web Audio API)
        function playCallChime() {
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                
                // Double chime: low then high
                playNote(audioCtx, 587.33, 0.15, 0); // D5
                playNote(audioCtx, 880.00, 0.30, 0.15); // A5
            } catch (e) {
                console.error('AudioContext chime failed:', e);
            }
        }

        function playNote(context, frequency, duration, startTimeOffset) {
            const osc = context.createOscillator();
            const gain = context.createGain();
            
            osc.connect(gain);
            gain.connect(context.destination);
            
            osc.type = 'sine';
            osc.frequency.setValueAtTime(frequency, context.currentTime + startTimeOffset);
            
            gain.gain.setValueAtTime(0, context.currentTime + startTimeOffset);
            gain.gain.linearRampToValueAtTime(0.15, context.currentTime + startTimeOffset + 0.05);
            gain.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + startTimeOffset + duration);
            
            osc.start(context.currentTime + startTimeOffset);
            osc.stop(context.currentTime + startTimeOffset + duration);
        }

        // 3. AJAX Poller
        const servingEl = document.getElementById('nowServingNumber');
        const waitingEl = document.getElementById('waitingListContainer');
        const calledEl = document.getElementById('calledListContainer');

        function pollQueueData() {
            fetch('<?= url('/queue/display-data') ?>')
                .then(response => {
                    if (!response.ok) throw new Error('Display poll error');
                    return response.json();
                })
                .then(data => {
                    // Update Now Serving
                    if (data.serving !== currentServing) {
                        servingEl.textContent = data.serving;
                        
                        // Don't chime on page initial load
                        if (!initialLoad && data.serving !== '000') {
                            playCallChime();
                        }
                        currentServing = data.serving;
                    }
                    initialLoad = false;

                    // Update Waiting Queue List
                    if (data.waiting.length === 0) {
                        waitingEl.innerHTML = '<span class="text-muted small">No patients in waiting line.</span>';
                    } else {
                        waitingEl.innerHTML = data.waiting.map(no => 
                            `<span class="queue-number-badge">${no}</span>`
                        ).join('');
                    }

                    // Update Recently Called List
                    if (data.called.length === 0) {
                        calledEl.innerHTML = '<span class="text-muted small">No recently called numbers.</span>';
                    } else {
                        calledEl.innerHTML = data.called.map((no, idx) => {
                            const extraClass = (idx === 0 && data.serving === no) ? 'first-called' : '';
                            return `<span class="queue-number-badge ${extraClass}">${no}</span>`;
                        }).join('');
                    }
                })
                .catch(error => {
                    console.error('Queue poll error:', error);
                });
        }

        // Poll every 5 seconds
        setInterval(pollQueueData, 5000);
        pollQueueData();
    });
    </script>
</body>
</html>
