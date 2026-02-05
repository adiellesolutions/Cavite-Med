<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password | CAVMED</title>
    <link rel="stylesheet" href="../css/main.css">
    <script defer src="../js/force_change_password.js"></script>
    <script defer src="../js/force_change_togglepassword.js"></script>
</head>
<body class="bg-background min-h-screen">
    <!-- Header Section -->
    <header class="bg-surface border-b border-border shadow-sm sticky top-0 z-sticky">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo and Title -->
                <div class="flex items-center gap-4">
                    <svg class="w-10 h-10" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="40" height="40" rx="8" fill="#2563EB"/>
                        <path d="M20 10v20M10 20h20" stroke="white" stroke-width="3" stroke-linecap="round"/>
                        <circle cx="20" cy="20" r="6" stroke="white" stroke-width="2" fill="none"/>
                    </svg>
                    <div>
                        <h1 class="text-xl font-semibold text-text-primary">CAVMED Portal</h1>
                    </div>
                </div>

            </div>
        </div>

        <!-- Navigation Tabs -->
        <nav class="border-t border-border bg-secondary-50">
            <div class="px-6">
                <div class="flex items-center gap-1 overflow-x-auto scrollbar-thin">
                    <a href="../backend/system_logout.php" class="nav-item whitespace-nowrap ml-auto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Logout
                    </a>
                </div>
            </div>
        </nav>
    </header>

<main class="flex items-center justify-center p-6 bg-background ">

    <div class="card max-w-md w-full">
        <h2 class="text-xl font-semibold mb-2 text-center">
            Change Your Password
        </h2>

        <p class="text-text-secondary mb-6 text-center">
            You must change your default password before continuing.
        </p>

        <form id="changePasswordForm" class="space-y-4">

            <!-- New Password -->
            <div class="relative">
                <input type="password" name="new_password" required
                       class="input w-full"
                       placeholder="New password">

                <button type="button"
                        class="togglePassword absolute inset-y-0 right-0 flex items-center pr-3 text-text-tertiary hover:text-text-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M2.458 12C3.732 7.943 7.523 5 12 5
                                 c4.478 0 8.268 2.943 9.542 7
                                 -1.274 4.057-5.064 7-9.542 7
                                 -4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>

            <!-- Confirm Password -->
            <div class="relative">
                <input type="password" name="confirm_password" required
                       class="input w-full"
                       placeholder="Confirm password">

                <button type="button"
                        class="togglePassword absolute inset-y-0 right-0 flex items-center pr-3 text-text-tertiary hover:text-text-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M2.458 12C3.732 7.943 7.523 5 12 5
                                 c4.478 0 8.268 2.943 9.542 7
                                 -1.274 4.057-5.064 7-9.542 7
                                 -4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>

            <button class="btn btn-primary w-full">
                Update Password
            </button>

        </form>
    </div>

</main>

</body>
</html>
