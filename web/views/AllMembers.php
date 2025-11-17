<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$prefix = '../';

//////////////////////////////////
//                              //
// !!After have login feature!! //
//                              //
//////////////////////////////////

// if (!isset($_SESSION['user'])) {
//     header('Location: ../../views/LoginForm.php');
//     exit;
// }

// if ($_SESSION['user']['role'] !== 'admin') {
//     header('Location: ../../views/403.php'); // or redirect to home
//     exit;
// }

$pageTitle = 'All Members - Admin Dashboard';

// Get current sort parameters
$currentSortBy = isset($currentSort['sortBy']) ? $currentSort['sortBy'] : 'created_at';
$currentSortOrder = isset($currentSort['sortOrder']) ? $currentSort['sortOrder'] : 'DESC';

// Helper function to generate sort URL
function getSortUrl($column, $currentSortBy, $currentSortOrder) {
    $params = ['action' => 'showAll'];
    
    // Preserve search parameter
    if (!empty($_GET['search'])) {
        $params['search'] = $_GET['search'];
    }
    
    // Preserve page parameter
    if (!empty($_GET['page'])) {
        $params['page'] = $_GET['page'];
    }
    
    // Determine sort order
    if ($currentSortBy === $column && $currentSortOrder === 'ASC') {
        $params['sortBy'] = $column;
        $params['sortOrder'] = 'DESC';
    } else {
        $params['sortBy'] = $column;
        $params['sortOrder'] = 'ASC';
    }
    
    return 'MemberController.php?' . http_build_query($params);
}

// Helper function to get sort arrow icon
function getSortArrow($column, $currentSortBy, $currentSortOrder) {
    if ($currentSortBy !== $column) {
        // No sort - show both arrows (neutral)
        return '<span class="material-symbols-outlined text-gray-400" style="font-size: 16px;">unfold_more</span>';
    } else {
        // Show active arrow
        if ($currentSortOrder === 'ASC') {
            return '<span class="material-symbols-outlined text-primary" style="font-size: 16px;">arrow_upward</span>';
        } else {
            return '<span class="material-symbols-outlined text-primary" style="font-size: 16px;">arrow_downward</span>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - REDSTORE</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#FF523B",
                        "background-light": "#FFF",
                        "background-dark": "#1a202c",
                        "card-light": "#F8F9FA",
                        "card-dark": "#2d3748",
                        "text-light": "#555",
                        "text-dark": "#cbd5e0",
                        "border-light": "#d1d5db",
                        "border-dark": "#4a5568",
                    },
                    fontFamily: {
                        display: ["Poppins", "sans-serif"],
                    },
                    borderRadius: {
                        DEFAULT: "0.5rem",
                    },
                },
            },
        };
    </script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 20
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark font-display text-text-light dark:text-text-dark transition-colors duration-300">

    <?php include $prefix . 'general/_header.php'; ?>
    <?php include $prefix . 'general/_navbar.php'; ?>

    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <header class="flex flex-col sm:flex-row justify-between items-center mb-8">
                <div class="flex items-center mb-4 sm:mb-0">
                    <svg class="h-10 w-auto" fill="none" viewBox="0 0 162 42" xmlns="http://www.w3.org/2000/svg">
                        <text fill="#FF523B" font-family="Poppins, sans-serif" font-size="28" font-weight="bold" letter-spacing="0em" style="white-space: pre" xml:space="preserve">
                            <tspan x="0" y="29.9219">REDSTORE</tspan>
                        </text>
                        <text class="dark:fill-gray-300" fill="#555" font-family="Poppins, sans-serif" font-size="8" font-style="italic" letter-spacing="0.05em" style="white-space: pre" xml:space="preserve">
                            <tspan x="100" y="38">athlete's choice</tspan>
                        </text>
                        <rect height="42" rx="4" stroke="#FF523B" stroke-width="2" width="95" x="0" y="0"></rect>
                    </svg>
                </div>
                <div class="text-right">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">Admin Dashboard</h1>
                    <p class="text-sm text-text-light dark:text-text-dark">Manage Members</p>
                </div>
            </header>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                    <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                    <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <!-- Main Content Card -->
            <div class="bg-card-light dark:bg-card-dark rounded-xl shadow-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">All Registered Members</h2>

                    <!-- Search and Actions Bar -->
                    <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 mb-6">
                        <div class="w-full md:w-1/2">
                            <form method="GET" action="MemberController.php" class="flex items-center">
                                <input type="hidden" name="action" value="showAll">
                                <?php if (!empty($_GET['sortBy'])): ?>
                                    <input type="hidden" name="sortBy" value="<?php echo htmlspecialchars($_GET['sortBy']); ?>">
                                <?php endif; ?>
                                <?php if (!empty($_GET['sortOrder'])): ?>
                                    <input type="hidden" name="sortOrder" value="<?php echo htmlspecialchars($_GET['sortOrder']); ?>">
                                <?php endif; ?>
                                <label class="sr-only" for="simple-search">Search</label>
                                <div class="relative w-full">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <span class="material-symbols-outlined text-gray-500 dark:text-gray-400">search</span>
                                    </div>
                                    <input
                                        class="bg-gray-50 border border-border-light text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-full pl-10 p-2.5 dark:bg-gray-700 dark:border-border-dark dark:placeholder-gray-400 dark:text-white"
                                        id="simple-search"
                                        name="search"
                                        placeholder="Search for members..."
                                        type="text"
                                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
                                </div>
                                <button type="submit" class="ml-2 py-2.5 px-5 text-sm font-medium text-white bg-primary rounded-lg hover:bg-red-600 focus:ring-4 focus:outline-none focus:ring-red-300">
                                    Search
                                </button>
                            </form>
                        </div>
                        <div class="w-full md:w-auto flex flex-col md:flex-row space-y-2 md:space-y-0 items-stretch md:items-center justify-end md:space-x-3 flex-shrink-0">
                            <a href="../views/MemberRegisterForm.php?return_to=admin" class="w-full md:w-auto flex items-center justify-center py-2 px-4 text-sm font-medium text-white bg-primary rounded-lg hover:bg-red-600 focus:ring-4 focus:ring-primary focus:outline-none">
                                <span class="material-symbols-outlined mr-2">add</span>
                                Add new member
                            </a>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3" scope="col">
                                    <a href="<?php echo getSortUrl('username', $currentSortBy, $currentSortOrder); ?>" class="flex items-center space-x-1 hover:text-primary">
                                        <span>Username</span>
                                        <?php echo getSortArrow('username', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="px-6 py-3" scope="col">
                                    <a href="<?php echo getSortUrl('full_name', $currentSortBy, $currentSortOrder); ?>" class="flex items-center space-x-1 hover:text-primary">
                                        <span>Full Name</span>
                                        <?php echo getSortArrow('full_name', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="px-6 py-3" scope="col">
                                    <a href="<?php echo getSortUrl('email', $currentSortBy, $currentSortOrder); ?>" class="flex items-center space-x-1 hover:text-primary">
                                        <span>Email</span>
                                        <?php echo getSortArrow('email', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="px-6 py-3" scope="col">
                                    <a href="<?php echo getSortUrl('contact_no', $currentSortBy, $currentSortOrder); ?>" class="flex items-center space-x-1 hover:text-primary">
                                        <span>Contact Number</span>
                                        <?php echo getSortArrow('contact_no', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="px-6 py-3" scope="col">
                                    <a href="<?php echo getSortUrl('gender', $currentSortBy, $currentSortOrder); ?>" class="flex items-center space-x-1 hover:text-primary">
                                        <span>Gender</span>
                                        <?php echo getSortArrow('gender', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="px-6 py-3" scope="col">
                                    <a href="<?php echo getSortUrl('created_at', $currentSortBy, $currentSortOrder); ?>" class="flex items-center space-x-1 hover:text-primary">
                                        <span>Joined Date</span>
                                        <?php echo getSortArrow('created_at', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="px-6 py-3" scope="col">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($members)): ?>
                                <?php foreach ($members as $member): ?>
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                            <?php echo htmlspecialchars($member['username']); ?>
                                        </td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($member['full_name']); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($member['email']); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($member['contact_no']); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($member['gender']); ?></td>
                                        <td class="px-6 py-4">
                                            <?php
                                            $date = new DateTime($member['created_at']);
                                            echo $date->format('Y-m-d');
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button
                                                onclick="openEditModal(<?php echo $member['user_id']; ?>, '<?php echo htmlspecialchars($member['username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($member['full_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($member['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($member['contact_no'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($member['gender'], ENT_QUOTES); ?>')"
                                                class="font-medium text-primary hover:underline">
                                                Edit
                                            </button>

                                            <button
                                                onclick="confirmDelete(<?php echo $member['user_id']; ?>, '<?php echo htmlspecialchars($member['full_name'], ENT_QUOTES); ?>')"
                                                class="font-medium text-red-600 dark:text-red-500 hover:underline ml-4">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="bg-white dark:bg-gray-800">
                                    <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        No members found. <?php echo !empty($_GET['search']) ? 'Try a different search term.' : ''; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if (!empty($members)): ?>
                    <nav aria-label="Table navigation" class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-3 md:space-y-0 p-4">
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            Showing
                            <span class="font-semibold text-gray-900 dark:text-white"><?php echo $pagination['showing_from']; ?>-<?php echo $pagination['showing_to']; ?></span>
                            of
                            <span class="font-semibold text-gray-900 dark:text-white"><?php echo $pagination['total_members']; ?></span>
                        </span>
                        <ul class="inline-flex items-stretch -space-x-px">
                            <!-- Previous Button -->
                            <li>
                                <?php 
                                $prevParams = ['action' => 'showAll', 'page' => $pagination['current_page'] - 1];
                                if (!empty($_GET['search'])) $prevParams['search'] = $_GET['search'];
                                if (!empty($_GET['sortBy'])) $prevParams['sortBy'] = $_GET['sortBy'];
                                if (!empty($_GET['sortOrder'])) $prevParams['sortOrder'] = $_GET['sortOrder'];
                                $prevUrl = 'MemberController.php?' . http_build_query($prevParams);
                                ?>
                                <?php if ($pagination['current_page'] > 1): ?>
                                    <a href="<?php echo $prevUrl; ?>"
                                        class="flex items-center justify-center h-full py-1.5 px-3 ml-0 text-gray-500 bg-white rounded-l-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                                        <span class="material-symbols-outlined text-lg">chevron_left</span>
                                    </a>
                                <?php else: ?>
                                    <span class="flex items-center justify-center h-full py-1.5 px-3 ml-0 text-gray-300 bg-white rounded-l-lg border border-gray-300 cursor-not-allowed dark:bg-gray-800 dark:border-gray-700 dark:text-gray-600">
                                        <span class="material-symbols-outlined text-lg">chevron_left</span>
                                    </span>
                                <?php endif; ?>
                            </li>

                            <!-- Page Numbers -->
                            <?php
                            $startPage = max(1, $pagination['current_page'] - 2);
                            $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);

                            for ($i = $startPage; $i <= $endPage; $i++):
                                $pageParams = ['action' => 'showAll', 'page' => $i];
                                if (!empty($_GET['search'])) $pageParams['search'] = $_GET['search'];
                                if (!empty($_GET['sortBy'])) $pageParams['sortBy'] = $_GET['sortBy'];
                                if (!empty($_GET['sortOrder'])) $pageParams['sortOrder'] = $_GET['sortOrder'];
                                $pageUrl = 'MemberController.php?' . http_build_query($pageParams);
                            ?>
                                <li>
                                    <a href="<?php echo $pageUrl; ?>"
                                        class="flex items-center justify-center text-sm py-2 px-3 leading-tight <?php echo $i == $pagination['current_page'] ? 'z-10 text-primary bg-red-50 border border-primary hover:bg-red-100' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700'; ?> dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- Next Button -->
                            <li>
                                <?php 
                                $nextParams = ['action' => 'showAll', 'page' => $pagination['current_page'] + 1];
                                if (!empty($_GET['search'])) $nextParams['search'] = $_GET['search'];
                                if (!empty($_GET['sortBy'])) $nextParams['sortBy'] = $_GET['sortBy'];
                                if (!empty($_GET['sortOrder'])) $nextParams['sortOrder'] = $_GET['sortOrder'];
                                $nextUrl = 'MemberController.php?' . http_build_query($nextParams);
                                ?>
                                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                    <a href="<?php echo $nextUrl; ?>"
                                        class="flex items-center justify-center h-full py-1.5 px-3 leading-tight text-gray-500 bg-white rounded-r-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                                        <span class="material-symbols-outlined text-lg">chevron_right</span>
                                    </a>
                                <?php else: ?>
                                    <span class="flex items-center justify-center h-full py-1.5 px-3 leading-tight text-gray-300 bg-white rounded-r-lg border border-gray-300 cursor-not-allowed dark:bg-gray-800 dark:border-gray-700 dark:text-gray-600">
                                        <span class="material-symbols-outlined text-lg">chevron_right</span>
                                    </span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal (Hidden Form) -->
    <form id="deleteForm" method="POST" action="MemberController.php" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="user_id" id="deleteUserId">
    </form>

    <?php include __DIR__ . '/../general/_footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function openEditModal(userId, username, fullName, email, contactNo, gender) {
            $('#editUserId').val(userId);
            $('#editUsername').val(username);
            $('#editFullName').val(fullName);
            $('#editEmail').val(email);
            $('#editContactNo').val(contactNo);
            $('#editGender').val(gender);

            $('#editModal').removeClass('hidden');
        }

        function closeEditModal() {
            $('#editModal').addClass('hidden');
        }


        function confirmDelete(userId, userName) {
            if (confirm(`Are you sure you want to delete member: ${userName}?\n\nThis action cannot be undone.`)) {
                document.getElementById('deleteUserId').value = userId;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Edit Member</h3>

                <form id="editForm" method="POST" action="MemberController.php" class="space-y-4">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" id="editUserId">

                    <div>
                        <label class="block text-sm font-medium">Username</label>
                        <input type="text" name="username" id="editUsername" readonly
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white bg-gray-100 dark:bg-gray-600 cursor-not-allowed"
                            title="Username cannot be changed">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Full Name</label>
                        <input type="text" name="full_name" id="editFullName"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Email</label>
                        <input type="email" name="email" id="editEmail"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Contact Number</label>
                        <input type="text" name="contact_no" id="editContactNo"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Gender</label>
                        <select name="gender" id="editGender"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-300 rounded-md">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-md">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


</body>

</html>