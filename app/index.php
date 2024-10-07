<?php
// Handle track updates
if (isset($_POST['update_tracks'])) {
    $tracks_data = json_decode($_POST['tracks_data'], true);
    file_put_contents('progress.json', json_encode(['tracks' => $tracks_data], JSON_PRETTY_PRINT));
    echo "<script>alert('Track progress updated successfully.');</script>";
}

// Get existing tracks data
$tracks_json = file_get_contents('progress.json');
$tracks_data = json_decode($tracks_json, true);
if (!$tracks_data) {
    $tracks_data = ['tracks' => []];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PIKD Progress Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: radial-gradient(circle, rgba(17,24,39,1) 0%, rgba(2,0,36,1) 100%);
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen flex">
    <!-- Firebase initialization -->
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.14.0/firebase-app.js";
        import { getAuth, onAuthStateChanged, signOut } from "https://www.gstatic.com/firebasejs/10.14.0/firebase-auth.js";

        const firebaseConfig = {
            apiKey: "API_KEY",
            authDomain: "AUTH_DOMAIN.firebaseapp.com",
            projectId: "PROJECT_ID",
            storageBucket: "STORAGE_BUCKET.appspot.com",
            messagingSenderId: "MESSAGING_SENDER_ID",
            appId: "APP_ID"
        };

        const app = initializeApp(firebaseConfig);
        const auth = getAuth();

        onAuthStateChanged(auth, (user) => {
            if (!user) {
                window.location.href = "https://project-progresstracker.pikd.nl";
            }
        });

        window.logout = function() {
            signOut(auth).then(() => {
                window.location.href = "https://project-progresstracker.pikd.nl";
            }).catch((error) => {
                alert("Error logging out: " + error.message);
            });
        };
    </script>

    <!-- Sidebar with logo -->
    <div class="w-1/8 bg-black p-6">
        <div class="flex items-center">
            <img src="https://public.pikdcdn.com/img/placeholder-art.png" alt="Logo" class="h-48 w-48 mr-4">
            <div class="text-white">
                <h1 class="text-4xl mb-2">Release Title</h1>
                <p>Artist Name</p>
<br>
<p>Deadline:</p>
                <p id="countdown" class="mt-2"></p>
            </div>
        </div>
        <button onclick="logout()" class="w-full bg-red-600 p-2 rounded mt-8 hover:bg-red-700">Logout</button>
    </div>

    <!-- Main dashboard -->
    <div class="flex-1 p-6 overflow-y-auto">
        <h1 class="text-3xl mb-8">Album Progress Dashboard</h1>

        <!-- Track Progress Table -->
        <div class="overflow-x-auto mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl">Track Progress</h2>
                <button onclick="saveTrackChanges()" class="bg-green-600 px-4 py-2 rounded hover:bg-green-700">
                    Save Changes
                </button>
            </div>
            <table class="table-auto w-full text-left">
                <thead>
                    <tr class="border-b border-gray-600">
                        <th class="p-3">Track</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Notes</th>
                        <th class="p-3">Progress</th>
                    </tr>
                </thead>
                <tbody id="track-list" class="text-gray-300">
                    <!-- Track rows will be inserted by JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Hidden form for updating tracks -->
        <form id="update-tracks-form" method="post" style="display: none;">
            <input type="hidden" name="update_tracks" value="1">
            <input type="hidden" id="tracks-data-input" name="tracks_data">
        </form>
    </div>

    <script>
        let tracksData = <?php echo json_encode($tracks_data['tracks']); ?>;

        // Function to render track list
        function renderTrackList() {
            const trackList = document.getElementById('track-list');
            trackList.innerHTML = '';

            tracksData.forEach((track, index) => {
                const row = document.createElement('tr');
                row.classList.add('border-b', 'border-gray-600');
                
                const statusOptions = ['Not Started', 'In Progress', 'Completed']
                    .map(status => `<option value="${status}" ${track.status === status ? 'selected' : ''}>${status}</option>`)
                    .join('');

                row.innerHTML = `
                    <td class="p-3">${escapeHtml(track.name)}</td>
                    <td class="p-3">
                        <select onchange="updateTrackStatus(${index}, this.value)" 
                                class="bg-gray-700 border border-gray-600 rounded p-1">
                            ${statusOptions}
                        </select>
                    </td>
                    <td class="p-3">
                        <textarea
                            onchange="updateTrackNotes(${index}, this.value)"
                            class="bg-gray-700 border border-gray-600 rounded p-1 w-full"
                            rows="2"
                        >${track.notes || ''}</textarea>
                    </td>
                    <td class="p-3"><span class="inline-block w-3 h-3 rounded-full ${getStatusColor(track.status)}"></span></td>
                `;
                trackList.appendChild(row);
            });
        }

        // Function to update track status
        function updateTrackStatus(index, status) {
            tracksData[index].status = status;
        }

        // Function to update track notes
        function updateTrackNotes(index, notes) {
            tracksData[index].notes = notes;
        }

        // Function to save track changes
        function saveTrackChanges() {
            document.getElementById('tracks-data-input').value = JSON.stringify(tracksData);
            document.getElementById('update-tracks-form').submit();
        }

        function getStatusColor(status) {
            switch(status.toUpperCase()) {
                case "COMPLETED": return "bg-green-500";
                case "IN PROGRESS": return "bg-orange-500";
                default: return "bg-gray-500";
            }
        }

        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
// add your timelimit here
        function calculateDeadline() {
            const deadlineDate = new Date('December 25, 2025').getTime();
            const now = new Date().getTime();
            const timeDifference = deadlineDate - now;

            const days = Math.floor(timeDifference / (1000 * 60 * 60 * 24));
            const hours = Math.floor((timeDifference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((timeDifference % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeDifference % (1000 * 60)) / 1000);

            document.getElementById('countdown').innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
            setTimeout(calculateDeadline, 1000);
        }

        // Initialize
        renderTrackList();
        calculateDeadline();
    </script>
</body>
</html>
