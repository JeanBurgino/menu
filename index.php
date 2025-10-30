<?php
// Lade Bring! Konfiguration
$bringConfig = ['list_name' => 'OBI']; // Default
if (file_exists('bring-config.php')) {
    include 'bring-config.php';
    $bringConfig = [
        'list_uuid' => defined('BRING_LIST_UUID') ? BRING_LIST_UUID : '',
        'list_name' => defined('BRING_LIST_NAME') ? BRING_LIST_NAME : 'OBI'
    ];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menüplaner - Familie</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Bring! Konfiguration von PHP
        const BRING_CONFIG = <?php echo json_encode($bringConfig); ?>;
    </script>
    <style>
        /* Zusätzliche Styles für bessere UX */
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .slide-up {
            animation: slideUp 0.3s ease-out;
        }
        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
        
        /* Drag & Drop Styles */
        [draggable="true"] {
            cursor: move;
            user-select: none;
        }
        [draggable="true"]:active {
            cursor: grabbing;
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-orange-50 to-red-50 min-h-screen">
    
    <!-- Benutzer-Auswahl -->
    <div id="userSelection" class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md fade-in">
            <div class="text-center mb-8">
                <svg class="w-16 h-16 mx-auto text-orange-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Menüplaner</h1>
                <p class="text-gray-600">Wähle deinen Namen aus</p>
            </div>
            <div id="userList" class="space-y-3">
                <!-- Wird dynamisch gefüllt -->
            </div>
        </div>
    </div>

    <!-- Hauptanwendung -->
    <div id="mainApp" class="hidden">
        <!-- Header -->
        <div class="bg-white shadow-lg sticky top-0 z-10">
            <div class="max-w-7xl mx-auto px-4 py-4">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Menüplaner</h1>
                            <p class="text-sm text-gray-600">Hallo, <span id="currentUserName"></span>!</p>
                        </div>
                    </div>
                    
                    <div class="flex gap-2 flex-wrap">
                        <button onclick="randomizeWeekPlan()" class="flex items-center gap-2 px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span class="hidden sm:inline">Zufall</span>
                        </button>
                        
                        <button onclick="exportToBring()" class="flex items-center gap-2 px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span class="hidden sm:inline">Bring!</span>
                        </button>
                        
                        <button onclick="showRecipeManagement()" class="flex items-center gap-2 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span class="hidden sm:inline">Rezepte</span>
                        </button>
                        
                        <button onclick="saveWeekPlan()" class="flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                            </svg>
                            <span class="hidden sm:inline">Speichern</span>
                        </button>
                        
                        <button id="userManagementBtn" onclick="showUserManagement()" class="hidden flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <span class="hidden sm:inline">Benutzer</span>
                        </button>
                        
                        <button onclick="switchUser()" class="flex items-center gap-2 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wochenplan -->
        <div class="max-w-7xl mx-auto p-4">
            <div id="weekPlan" class="grid grid-cols-1 lg:grid-cols-7 gap-4 mt-6">
                <!-- Wird dynamisch gefüllt -->
            </div>
        </div>
    </div>

    <!-- Rezeptverwaltung Seite -->
    <div id="recipeManagementPage" class="hidden min-h-screen bg-gradient-to-br from-green-50 to-teal-50">
        <!-- Header -->
        <div class="bg-white shadow-lg sticky top-0 z-10">
            <div class="max-w-7xl mx-auto px-4 py-4">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Rezeptverwaltung</h1>
                            <p class="text-sm text-gray-600"><span id="recipeCount">0</span> Rezepte</p>
                        </div>
                    </div>
                    
                    <div class="flex gap-2 items-center flex-wrap">
                        <input
                            type="text"
                            id="recipeSearchInput"
                            placeholder="Rezepte suchen..."
                            oninput="filterRecipes()"
                            class="px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:outline-none"
                        />
                        
                        <button onclick="showAddRecipeModal()" class="flex items-center gap-2 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Neues Rezept
                        </button>
                        
                        <button onclick="hideRecipeManagement()" class="flex items-center gap-2 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Zurück
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rezeptliste -->
        <div class="max-w-7xl mx-auto p-4">
            <div id="recipesList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
                <!-- Wird dynamisch gefüllt -->
            </div>
        </div>
    </div>

    <!-- Modal: Rezept hinzufügen -->
    <div id="recipeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-20">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto slide-up">
            <h2 id="recipeModalTitle" class="text-2xl font-bold text-gray-800 mb-4">Neues Rezept</h2>
            
            <div class="space-y-4">
                <!-- Titel -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titel *</label>
                    <input
                        type="text"
                        id="recipeTitle"
                        placeholder="z.B. Spaghetti Bolognese"
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:outline-none"
                    />
                </div>

                <!-- Zutaten -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Zutaten</label>
                    <div id="ingredientsList" class="space-y-2">
                        <!-- Wird dynamisch gefüllt -->
                    </div>
                    <button
                        type="button"
                        onclick="addIngredientField()"
                        class="mt-2 flex items-center gap-2 px-3 py-2 text-sm bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Zutat hinzufügen
                    </button>
                </div>

                <!-- Kategorien -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kategorien</label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="recipeLunch" checked class="w-5 h-5 text-green-500 rounded focus:ring-green-500">
                            <span class="text-gray-700">Mittag</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="recipeDinner" checked class="w-5 h-5 text-green-500 rounded focus:ring-green-500">
                            <span class="text-gray-700">Abendessen</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="recipeWeekend" class="w-5 h-5 text-green-500 rounded focus:ring-green-500">
                            <span class="text-gray-700">Wochenende</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button
                    onclick="saveRecipe()"
                    class="flex-1 py-3 bg-gradient-to-r from-green-500 to-teal-500 text-white rounded-lg font-semibold hover:from-green-600 hover:to-teal-600 transition-all"
                >
                    Speichern
                </button>
                
                <button
                    onclick="hideRecipeForm()"
                    class="flex-1 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-all"
                >
                    Abbrechen
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Rezept auswählen -->
    <div id="selectRecipeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-20">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md max-h-[80vh] flex flex-col slide-up">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Rezept auswählen</h2>
                <button onclick="hideSelectRecipe()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Suchfeld -->
            <div class="mb-4">
                <input
                    type="text"
                    id="recipeModalSearch"
                    placeholder="🔍 Rezepte oder Zutaten suchen..."
                    oninput="filterRecipeModal()"
                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-orange-500 focus:outline-none"
                />
            </div>
            
            <div id="recipeSelectionList" class="space-y-2 flex-1 overflow-y-auto">
                <!-- Wird dynamisch gefüllt -->
            </div>
            
            <button
                onclick="clearSelectedMeal()"
                class="w-full mt-4 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors"
            >
                Mahlzeit entfernen
            </button>
        </div>
    </div>

    <!-- Modal: Benutzerverwaltung (nur für Papa) -->
    <div id="userManagementModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-20">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto slide-up">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Benutzerverwaltung</h2>
                <button onclick="hideUserManagement()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Neuen Benutzer hinzufügen -->
            <div class="bg-blue-50 p-4 rounded-lg mb-6">
                <h3 class="font-bold text-gray-700 mb-3">Neuer Benutzer</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Profilbild (Emoji)</label>
                        <div class="flex gap-2 flex-wrap mb-2">
                            <button onclick="selectEmoji('👨')" class="text-3xl p-2 hover:bg-white rounded-lg transition-colors">👨</button>
                            <button onclick="selectEmoji('👩')" class="text-3xl p-2 hover:bg-white rounded-lg transition-colors">👩</button>
                            <button onclick="selectEmoji('👦')" class="text-3xl p-2 hover:bg-white rounded-lg transition-colors">👦</button>
                            <button onclick="selectEmoji('👧')" class="text-3xl p-2 hover:bg-white rounded-lg transition-colors">👧</button>
                            <button onclick="selectEmoji('👶')" class="text-3xl p-2 hover:bg-white rounded-lg transition-colors">👶</button>
                            <button onclick="selectEmoji('👴')" class="text-3xl p-2 hover:bg-white rounded-lg transition-colors">👴</button>
                            <button onclick="selectEmoji('👵')" class="text-3xl p-2 hover:bg-white rounded-lg transition-colors">👵</button>
                            <button onclick="selectEmoji('🧑')" class="text-3xl p-2 hover:bg-white rounded-lg transition-colors">🧑</button>
                        </div>
                        <input
                            type="text"
                            id="newUserEmoji"
                            value="👤"
                            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none text-center text-2xl"
                            maxlength="2"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input
                            type="text"
                            id="newUserName"
                            placeholder="Benutzername"
                            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
                        />
                    </div>
                    <button
                        onclick="addUser()"
                        class="w-full py-2 bg-blue-500 text-white rounded-lg font-semibold hover:bg-blue-600 transition-colors"
                    >
                        Benutzer hinzufügen
                    </button>
                </div>
            </div>

            <!-- Liste der Benutzer -->
            <h3 class="font-bold text-gray-700 mb-3">Alle Benutzer</h3>
            <div id="userManagementList" class="space-y-3">
                <!-- Wird dynamisch gefüllt -->
            </div>
        </div>
    </div>

    <!-- Modal: Benutzer bearbeiten -->
    <div id="editUserModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-30">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md slide-up max-h-[90vh] overflow-y-auto">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Benutzer bearbeiten</h2>
            
            <div class="space-y-4">
                <!-- Profilbild Upload (NEU) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Profilbild</label>
                    
                    <!-- Preview -->
                    <div class="flex items-center gap-4 mb-3">
                        <div id="editUserPicturePreview" class="w-24 h-24 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden border-2 border-gray-300">
                            <span class="text-4xl">👤</span>
                        </div>
                        <div class="flex-1">
                            <input
                                type="file"
                                id="editUserPictureFile"
                                accept="image/*"
                                onchange="handleProfilePictureUpload(event)"
                                class="hidden"
                            />
                            <button
                                onclick="document.getElementById('editUserPictureFile').click()"
                                class="w-full py-2 px-4 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors text-sm font-medium"
                            >
                                📷 Bild hochladen
                            </button>
                            <button
                                onclick="removeProfilePicture()"
                                class="w-full mt-2 py-2 px-4 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors text-sm font-medium"
                            >
                                🗑️ Bild entfernen
                            </button>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input
                        type="text"
                        id="editUserName"
                        class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
                    />
                </div>
            </div>
            
            <div class="flex gap-3 mt-4">
                <button
                    onclick="saveEditUser()"
                    class="flex-1 py-3 bg-blue-500 text-white rounded-lg font-semibold hover:bg-blue-600 transition-all"
                >
                    Speichern
                </button>
                
                <button
                    onclick="hideEditUser()"
                    class="flex-1 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-all"
                >
                    Abbrechen
                </button>
            </div>
        </div>
    </div>

    <script>
        // Globale Variablen
        let currentUser = null;
        let users = [];
        let recipes = [];
        let weekPlan = {};
        let lockedMeals = new Set();
        let currentWeek = { weekNumber: 0, year: 0 };
        let selectedMeal = { day: null, type: null };
        let editingUser = null;
        let editingRecipe = null;
        let tempProfilePicture = null; // Temporär während des Bearbeitens

        const weekdays = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];
        const mealTypes = ['Mittag', 'Abendessen'];

        // Initialisierung beim Laden der Seite
        document.addEventListener('DOMContentLoaded', async () => {
            await loadUsers();
            await loadRecipes();
            
            // Prüfe ob ein User bereits angemeldet war
            const savedUserId = localStorage.getItem('currentUserId');
            if (savedUserId && users.length > 0) {
                const savedUser = users.find(u => u.id == savedUserId);
                if (savedUser) {
                    selectUser(savedUser);
                }
            }
        });

        // ==================== API CALLS ====================

        async function apiCall(endpoint, method = 'GET', data = null) {
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                }
            };

            if (data) {
                options.body = JSON.stringify(data);
            }

            try {
                // Entferne führenden Slash und extrahiere action und Parameter
                endpoint = endpoint.replace(/^\//, '');
                
                // Trenne action und Query-Parameter
                const [action, params] = endpoint.split('?');
                const baseAction = action.replace(/\//g, '_');
                
                // Baue URL zusammen
                let url = `api.php?action=${baseAction}`;
                if (params) {
                    url += `&${params}`;
                }
                
                const response = await fetch(url, options);
                
                // Prüfe ob Response OK ist
                if (!response.ok) {
                    const text = await response.text();
                    console.error('Server Response:', text);
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                return result;
            } catch (error) {
                console.error('API-Fehler:', error);
                alert('Fehler bei der Kommunikation mit dem Server: ' + error.message);
                return null;
            }
        }

        // ==================== BENUTZER FUNKTIONEN ====================

        async function loadUsers() {
            users = await apiCall('/users');
            displayUsers();
        }

        function displayUsers() {
            const userList = document.getElementById('userList');
            userList.innerHTML = '';

            users.forEach(user => {
                const button = document.createElement('button');
                button.className = 'w-full py-4 px-6 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-xl font-semibold hover:from-orange-600 hover:to-red-600 transition-all transform hover:scale-105 shadow-lg flex items-center gap-3 justify-center';
                
                // Zeige Profilbild oder Emoji
                const profileDisplay = user.profile_picture 
                    ? `<img src="${user.profile_picture}" alt="${user.name}" class="w-12 h-12 rounded-full object-cover border-2 border-white" />`
                    : `<span class="text-3xl">${user.profile_image || '👤'}</span>`;
                
                button.innerHTML = `
                    ${profileDisplay}
                    <span>${user.name}</span>
                `;
                button.onclick = () => selectUser(user);
                userList.appendChild(button);
            });
        }

        function selectUser(user) {
            currentUser = user;
            localStorage.setItem('currentUserId', user.id);
            document.getElementById('currentUserName').textContent = user.name;
            document.getElementById('userSelection').classList.add('hidden');
            document.getElementById('mainApp').classList.remove('hidden');
            
            // Benutzerverwaltungs-Button nur für Papa anzeigen
            const userMgmtBtn = document.getElementById('userManagementBtn');
            if (user.is_admin || user.name === 'Papa') {
                userMgmtBtn.classList.remove('hidden');
                userMgmtBtn.classList.add('flex');
            } else {
                userMgmtBtn.classList.add('hidden');
                userMgmtBtn.classList.remove('flex');
            }
            
            loadWeekPlan();
        }

        function switchUser() {
            document.getElementById('mainApp').classList.add('hidden');
            document.getElementById('userSelection').classList.remove('hidden');
            localStorage.removeItem('currentUserId');
            currentUser = null;
        }

        // ==================== REZEPTE FUNKTIONEN ====================

        async function loadRecipes() {
            recipes = await apiCall('/recipes');
        }

        function showRecipeForm() {
            document.getElementById('recipeModal').classList.remove('hidden');
            document.getElementById('recipeTitle').focus();
        }

        function hideRecipeForm() {
            document.getElementById('recipeModal').classList.add('hidden');
            document.getElementById('recipeTitle').value = '';
        }

        async function addRecipe() {
            const title = document.getElementById('recipeTitle').value.trim();
            
            if (!title) {
                alert('Bitte geben Sie einen Titel ein');
                return;
            }

            const newRecipe = await apiCall('/recipes', 'POST', {
                title: title,
                created_by: currentUser.id
            });

            if (newRecipe) {
                recipes.push(newRecipe);
                hideRecipeForm();
                alert('Rezept wurde hinzugefügt!');
            }
        }

        // ==================== WOCHENPLAN FUNKTIONEN ====================

        async function loadWeekPlan() {
            const data = await apiCall('/weekplan');
            
            if (data) {
                currentWeek = { weekNumber: data.weekNumber, year: data.year };
                weekPlan = data.plan;
                
                // Locked meals extrahieren
                lockedMeals.clear();
                Object.entries(weekPlan).forEach(([day, meals]) => {
                    Object.entries(meals).forEach(([type, meal]) => {
                        if (meal && meal.is_locked) {
                            lockedMeals.add(`${day}-${type}`);
                        }
                    });
                });
                
                displayWeekPlan();
            }
        }

        function displayWeekPlan() {
            const container = document.getElementById('weekPlan');
            container.innerHTML = '';

            weekdays.forEach(day => {
                const dayCard = document.createElement('div');
                dayCard.className = 'bg-white rounded-xl shadow-lg overflow-hidden fade-in';
                
                let dayHTML = `
                    <div class="bg-gradient-to-r from-orange-500 to-red-500 text-white p-4">
                        <h3 class="font-bold text-center">${day}</h3>
                    </div>
                `;

                mealTypes.forEach(meal => {
                    const mealKey = `${day}-${meal}`;
                    const isLocked = lockedMeals.has(mealKey);
                    const mealData = weekPlan[day]?.[meal];
                    const recipeTitle = mealData?.recipe_title || '';
                    const modifiedBy = mealData?.modified_by_name || '';
                    const modifiedByImage = mealData?.modified_by_image || '';
                    const modifiedByPicture = mealData?.modified_by_picture || '';

                    dayHTML += `
                        <div class="p-4 border-b last:border-b-0">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-semibold text-gray-700">${meal}</span>
                                <button 
                                    onclick="toggleLock('${day}', '${meal}')"
                                    class="p-1 rounded ${isLocked ? 'text-red-500' : 'text-gray-400'} hover:bg-gray-100"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        ${isLocked 
                                            ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>'
                                            : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>'
                                        }
                                    </svg>
                                </button>
                            </div>
                            
                            <div 
                                draggable="${recipeTitle ? 'true' : 'false'}"
                                data-day="${day}"
                                data-meal="${meal}"
                                ondragstart="handleDragStart(event)"
                                ondragover="handleDragOver(event)"
                                ondragleave="handleDragLeave(event)"
                                ondrop="handleDrop(event)"
                                ondragend="handleDragEnd(event)"
                                onclick="handleMealClick('${day}', '${meal}', event)"
                                class="min-h-16 p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors ${recipeTitle ? 'cursor-grab active:cursor-grabbing' : ''}"
                                style="touch-action: none;"
                            >
                                ${recipeTitle 
                                    ? `
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm font-medium text-gray-800">${recipeTitle}</span>
                                        ${modifiedBy ? `
                                        <div class="flex items-center gap-1 text-xs text-gray-500">
                                            ${modifiedByPicture 
                                                ? `<img src="${modifiedByPicture}" alt="${modifiedBy}" class="w-5 h-5 rounded-full object-cover border border-gray-300" />` 
                                                : modifiedByImage 
                                                    ? `<span class="text-base">${modifiedByImage}</span>` 
                                                    : ''}
                                            <span>${modifiedBy.charAt(0).toUpperCase()}</span>
                                        </div>
                                        ` : ''}
                                    </div>
                                    `
                                    : '<div class="flex items-center justify-center h-full"><span class="text-sm text-gray-400">Tippen zum Auswählen</span></div>'
                                }
                            </div>
                        </div>
                    `;
                });

                dayCard.innerHTML = dayHTML;
                container.appendChild(dayCard);
            });
        }

        // ==================== DRAG & DROP FUNKTIONEN ====================
        
        let draggedElement = null;
        let draggedMeal = null;
        let isDragging = false;

        function handleDragStart(event) {
            isDragging = true;
            draggedElement = event.target;
            draggedMeal = {
                day: event.target.dataset.day,
                meal: event.target.dataset.meal
            };
            event.target.style.opacity = '0.5';
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/html', event.target.innerHTML);
        }

        function handleDragOver(event) {
            if (event.preventDefault) {
                event.preventDefault();
            }
            event.dataTransfer.dropEffect = 'move';
            
            // Visuelle Hervorhebung
            const target = event.target.closest('[data-day][data-meal]');
            if (target && target !== draggedElement) {
                target.style.border = '2px dashed #f97316';
                target.style.backgroundColor = '#fff7ed';
            }
            
            return false;
        }

        function handleDragLeave(event) {
            const target = event.target.closest('[data-day][data-meal]');
            if (target && target !== draggedElement) {
                target.style.border = '';
                target.style.backgroundColor = '';
            }
        }

        function handleDrop(event) {
            if (event.stopPropagation) {
                event.stopPropagation();
            }
            event.preventDefault();
            
            const target = event.target.closest('[data-day][data-meal]');
            if (!target || target === draggedElement) {
                // Entferne Hervorhebung
                if (target) {
                    target.style.border = '';
                    target.style.backgroundColor = '';
                }
                return false;
            }
            
            const targetMeal = {
                day: target.dataset.day,
                meal: target.dataset.meal
            };
            
            // Tausche die Einträge
            swapMeals(draggedMeal, targetMeal);
            
            // Entferne visuelle Hervorhebung
            target.style.border = '';
            target.style.backgroundColor = '';
            
            return false;
        }

        function handleDragEnd(event) {
            event.target.style.opacity = '1';
            
            // Entferne alle visuellen Hervorhebungen
            document.querySelectorAll('[data-day][data-meal]').forEach(el => {
                el.style.border = '';
                el.style.backgroundColor = '';
            });
            
            // Reset nach kurzer Verzögerung damit onclick nicht triggert
            setTimeout(() => {
                isDragging = false;
            }, 100);
            
            draggedElement = null;
            draggedMeal = null;
        }

        function handleMealClick(day, meal, event) {
            // Nur öffnen wenn nicht gedragged wurde
            if (!isDragging) {
                showSelectRecipe(day, meal);
            }
        }

        function swapMeals(meal1, meal2) {
            if (!weekPlan[meal1.day]) weekPlan[meal1.day] = {};
            if (!weekPlan[meal2.day]) weekPlan[meal2.day] = {};
            
            // Speichere Einträge
            const temp = weekPlan[meal1.day][meal1.meal];
            weekPlan[meal1.day][meal1.meal] = weekPlan[meal2.day][meal2.meal];
            weekPlan[meal2.day][meal2.meal] = temp;
            
            // Aktualisiere Anzeige
            displayWeekPlan();
            
            // Zeige Bestätigung
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 animate-fade-in';
            toast.textContent = `✓ ${meal1.day} ${meal1.meal} ↔ ${meal2.day} ${meal2.meal}`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2000);
        }

        function showSelectRecipe(day, meal) {
            selectedMeal = { day, meal };
            document.getElementById('recipeModalSearch').value = '';
            filterRecipeModal();
            document.getElementById('selectRecipeModal').classList.remove('hidden');
        }

        function hideSelectRecipe() {
            document.getElementById('selectRecipeModal').classList.add('hidden');
            document.getElementById('recipeModalSearch').value = '';
            selectedMeal = { day: null, meal: null };
        }

        function filterRecipeModal() {
            const searchTerm = document.getElementById('recipeModalSearch').value.toLowerCase();
            const list = document.getElementById('recipeSelectionList');
            list.innerHTML = '';

            const filteredRecipes = recipes.filter(recipe => {
                // Suche im Titel
                if (recipe.title.toLowerCase().includes(searchTerm)) return true;
                
                // Suche in Zutaten
                if (recipe.ingredient_list) {
                    return recipe.ingredient_list.some(ing => 
                        ing.name.toLowerCase().includes(searchTerm) ||
                        (ing.specification && ing.specification.toLowerCase().includes(searchTerm))
                    );
                }
                
                return false;
            });

            if (filteredRecipes.length === 0) {
                list.innerHTML = '<div class="text-center text-gray-500 py-4">Keine Rezepte gefunden</div>';
                return;
            }

            filteredRecipes.forEach(recipe => {
                const button = document.createElement('button');
                button.className = 'w-full p-3 text-left text-sm bg-orange-50 hover:bg-orange-100 rounded transition-colors recipe-item';
                button.textContent = recipe.title;
                button.onclick = () => selectRecipeForMeal(recipe);
                list.appendChild(button);
            });
        }

        async function selectRecipeForMeal(recipe) {
            const { day, meal } = selectedMeal;
            
            if (!weekPlan[day]) {
                weekPlan[day] = {};
            }
            
            weekPlan[day][meal] = {
                recipe_id: recipe.id,
                recipe_title: recipe.title,
                is_locked: lockedMeals.has(`${day}-${meal}`),
                last_modified_by: currentUser.id,
                modified_by_name: currentUser.name,
                modified_by_image: currentUser.profile_image,
                modified_by_picture: currentUser.profile_picture
            };

            await apiCall('/update_meal', 'POST', {
                weekNumber: currentWeek.weekNumber,
                year: currentWeek.year,
                weekday: day,
                mealType: meal,
                recipe_id: recipe.id,
                is_locked: lockedMeals.has(`${day}-${meal}`),
                user_id: currentUser.id
            });

            displayWeekPlan();
            hideSelectRecipe();
        }

        async function clearSelectedMeal() {
            const { day, meal } = selectedMeal;
            
            if (weekPlan[day]) {
                weekPlan[day][meal] = null;
            }

            await apiCall('/update_meal', 'POST', {
                weekNumber: currentWeek.weekNumber,
                year: currentWeek.year,
                weekday: day,
                mealType: meal,
                recipe_id: null,
                user_id: currentUser.id
            });

            displayWeekPlan();
            hideSelectRecipe();
        }

        function toggleLock(day, meal) {
            const mealKey = `${day}-${meal}`;
            
            if (lockedMeals.has(mealKey)) {
                lockedMeals.delete(mealKey);
            } else {
                lockedMeals.add(mealKey);
            }

            if (weekPlan[day]?.[meal]) {
                weekPlan[day][meal].is_locked = lockedMeals.has(mealKey);
            }

            apiCall('/toggle_lock', 'POST', {
                weekNumber: currentWeek.weekNumber,
                year: currentWeek.year,
                weekday: day,
                mealType: meal
            });

            displayWeekPlan();
        }

        async function randomizeWeekPlan() {
            if (recipes.length === 0) {
                alert('Bitte fügen Sie zuerst Rezepte hinzu!');
                return;
            }

            const usedRecipes = new Set(); // Tracking für keine Duplikate in einer Woche
            const weekdaysForDinner = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag'];

            weekdays.forEach(day => {
                mealTypes.forEach(meal => {
                    const mealKey = `${day}-${meal}`;
                    
                    if (!lockedMeals.has(mealKey)) {
                        if (!weekPlan[day]) {
                            weekPlan[day] = {};
                        }
                        
                        // Spezialfall: "Chalt Nachtessen" für Mo-Fr Abendessen
                        if (meal === 'Abendessen' && weekdaysForDinner.includes(day)) {
                            weekPlan[day][meal] = {
                                recipe_id: 21, // Feste ID für "Chalt Nachtessen"
                                recipe_title: 'Chalt Nachtessen',
                                is_locked: false,
                                last_modified_by: currentUser.id,
                                modified_by_name: currentUser.name,
                                modified_by_image: currentUser.profile_image,
                                modified_by_picture: currentUser.profile_picture
                            };
                        } else {
                            // Wähle zufälliges Rezept, das noch nicht verwendet wurde
                            let attempts = 0;
                            let randomRecipe;
                            
                            do {
                                randomRecipe = recipes[Math.floor(Math.random() * recipes.length)];
                                attempts++;
                            } while (usedRecipes.has(randomRecipe.id) && attempts < recipes.length);
                            
                            // Wenn alle Rezepte verwendet wurden, erlaube Wiederholungen
                            if (attempts >= recipes.length) {
                                usedRecipes.clear();
                            }
                            
                            usedRecipes.add(randomRecipe.id);
                            
                            weekPlan[day][meal] = {
                                recipe_id: randomRecipe.id,
                                recipe_title: randomRecipe.title,
                                is_locked: false,
                                last_modified_by: currentUser.id,
                                modified_by_name: currentUser.name,
                                modified_by_image: currentUser.profile_image,
                                modified_by_picture: currentUser.profile_picture
                            };
                        }
                    }
                });
            });

            displayWeekPlan();
        }

        async function saveWeekPlan() {
            const result = await apiCall('/weekplan', 'POST', {
                weekNumber: currentWeek.weekNumber,
                year: currentWeek.year,
                plan: weekPlan,
                user_id: currentUser.id
            });

            if (result && result.success) {
                alert('Wochenplan wurde gespeichert!');
            }
        }

        // Enter-Taste im Rezept-Modal
        document.getElementById('recipeTitle')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                addRecipe();
            }
        });

        // ==================== BENUTZERVERWALTUNG ====================

        function showUserManagement() {
            if (!currentUser || (!currentUser.is_admin && currentUser.name !== 'Papa')) {
                alert('Keine Berechtigung!');
                return;
            }
            
            displayUserManagementList();
            document.getElementById('userManagementModal').classList.remove('hidden');
        }

        function hideUserManagement() {
            document.getElementById('userManagementModal').classList.add('hidden');
            document.getElementById('newUserName').value = '';
            document.getElementById('newUserEmoji').value = '👤';
        }

        function displayUserManagementList() {
            const list = document.getElementById('userManagementList');
            list.innerHTML = '';

            users.forEach(user => {
                const div = document.createElement('div');
                div.className = 'bg-gray-50 p-4 rounded-lg flex items-center justify-between';
                
                div.innerHTML = `
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">${user.profile_image || '👤'}</span>
                        <div>
                            <div class="font-semibold text-gray-800">${user.name}</div>
                            ${user.is_admin ? '<span class="text-xs text-blue-600 font-medium">Admin</span>' : ''}
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button
                            onclick="editUser(${user.id})"
                            class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors"
                            title="Bearbeiten"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        ${user.name !== 'Papa' ? `
                        <button
                            onclick="confirmDeleteUser(${user.id}, '${user.name}')"
                            class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors"
                            title="Löschen"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                        ` : ''}
                    </div>
                `;
                
                list.appendChild(div);
            });
        }

        function selectEmoji(emoji) {
            document.getElementById('newUserEmoji').value = emoji;
        }

        async function addUser() {
            const name = document.getElementById('newUserName').value.trim();
            const emoji = document.getElementById('newUserEmoji').value.trim();
            
            if (!name) {
                alert('Bitte geben Sie einen Namen ein');
                return;
            }

            const newUser = await apiCall('/users', 'POST', {
                name: name,
                profile_image: emoji || '👤',
                is_admin: false
            });

            if (newUser) {
                users.push(newUser);
                displayUserManagementList();
                displayUsers();
                document.getElementById('newUserName').value = '';
                document.getElementById('newUserEmoji').value = '👤';
                alert('Benutzer wurde hinzugefügt!');
            }
        }

        function editUser(userId) {
            const user = users.find(u => u.id == userId);
            if (!user) return;
            
            editingUser = user;
            tempProfilePicture = user.profile_picture || null;
            
            document.getElementById('editUserName').value = user.name;
            
            // Zeige Profilbild-Preview
            updateProfilePicturePreview(user.profile_picture, user.profile_image);
            
            document.getElementById('editUserModal').classList.remove('hidden');
        }

        function hideEditUser() {
            document.getElementById('editUserModal').classList.add('hidden');
            editingUser = null;
            tempProfilePicture = null;
        }

        function updateProfilePicturePreview(picture, fallbackEmoji) {
            const preview = document.getElementById('editUserPicturePreview');
            if (picture) {
                preview.innerHTML = `<img src="${picture}" alt="Profilbild" class="w-full h-full object-cover" />`;
            } else {
                preview.innerHTML = `<span class="text-4xl">${fallbackEmoji || '👤'}</span>`;
            }
        }

        function handleProfilePictureUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            // Validierung
            if (!file.type.startsWith('image/')) {
                alert('Bitte wählen Sie eine Bilddatei');
                return;
            }
            
            // Max 2MB
            if (file.size > 2 * 1024 * 1024) {
                alert('Bild ist zu groß (max 2MB)');
                return;
            }
            
            // Erstelle Image Element zum Komprimieren
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    // Erstelle Canvas zum Komprimieren
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    
                    // Maximale Größe: 200x200px
                    let width = img.width;
                    let height = img.height;
                    const maxSize = 200;
                    
                    if (width > height && width > maxSize) {
                        height = (height / width) * maxSize;
                        width = maxSize;
                    } else if (height > maxSize) {
                        width = (width / height) * maxSize;
                        height = maxSize;
                    }
                    
                    canvas.width = width;
                    canvas.height = height;
                    
                    // Zeichne Bild
                    ctx.drawImage(img, 0, 0, width, height);
                    
                    // Konvertiere zu Base64 (JPEG mit 80% Qualität für kleinere Dateigröße)
                    tempProfilePicture = canvas.toDataURL('image/jpeg', 0.8);
                    
                    // Update Preview
                    updateProfilePicturePreview(tempProfilePicture, '👤');
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        function removeProfilePicture() {
            tempProfilePicture = null;
            updateProfilePicturePreview(null, editingUser?.profile_image || '👤');
        }

        async function saveEditUser() {
            if (!editingUser) return;
            
            const name = document.getElementById('editUserName').value.trim();
            
            if (!name) {
                alert('Bitte geben Sie einen Namen ein');
                return;
            }

            const updated = await apiCall(`/update_user?id=${editingUser.id}`, 'POST', {
                name: name,
                profile_image: editingUser.profile_image || '👤',
                profile_picture: tempProfilePicture
            });

            if (updated && updated.success) {
                // User-Liste von DB neu laden um sicherzustellen dass alles aktuell ist
                await loadUsers();
                
                // Wenn aktueller User bearbeitet wurde
                if (currentUser && currentUser.id == editingUser.id) {
                    const updatedUser = users.find(u => u.id == editingUser.id);
                    if (updatedUser) {
                        currentUser = updatedUser;
                        document.getElementById('currentUserName').textContent = updatedUser.name;
                    }
                }
                
                displayUserManagementList();
                displayUsers();
                displayWeekPlan(); // Wochenplan neu laden um Icons zu aktualisieren
                hideEditUser();
                alert('Benutzer wurde aktualisiert!');
            }
        }

        function confirmDeleteUser(userId, userName) {
            if (confirm(`Möchten Sie den Benutzer "${userName}" wirklich löschen?`)) {
                deleteUserById(userId);
            }
        }

        async function deleteUserById(userId) {
            const result = await apiCall(`/delete_user?id=${userId}`, 'POST');

            if (result && result.success) {
                users = users.filter(u => u.id != userId);
                displayUserManagementList();
                displayUsers();
                alert('Benutzer wurde gelöscht!');
            }
        }

        // ==================== REZEPTVERWALTUNG ====================

        function showRecipeManagement() {
            document.getElementById('mainApp').classList.add('hidden');
            document.getElementById('recipeManagementPage').classList.remove('hidden');
            displayRecipesList();
        }

        function hideRecipeManagement() {
            document.getElementById('recipeManagementPage').classList.add('hidden');
            document.getElementById('mainApp').classList.remove('hidden');
        }

        function displayRecipesList() {
            filterRecipes();
        }

        function filterRecipes() {
            const searchTerm = document.getElementById('recipeSearchInput')?.value.toLowerCase() || '';
            const list = document.getElementById('recipesList');
            const count = document.getElementById('recipeCount');
            list.innerHTML = '';

            const filteredRecipes = recipes.filter(recipe => {
                // Suche im Titel
                if (recipe.title.toLowerCase().includes(searchTerm)) return true;
                
                // Suche in Zutaten
                if (recipe.ingredient_list) {
                    return recipe.ingredient_list.some(ing => 
                        ing.name.toLowerCase().includes(searchTerm) ||
                        (ing.specification && ing.specification.toLowerCase().includes(searchTerm))
                    );
                }
                
                return false;
            });

            count.textContent = `${filteredRecipes.length} von ${recipes.length}`;

            filteredRecipes.forEach(recipe => {
                const card = document.createElement('div');
                card.className = 'bg-white rounded-xl shadow-lg p-5 hover:shadow-xl transition-shadow cursor-pointer';
                
                const categories = [];
                if (recipe.is_lunch) categories.push('🍽️ Mittag');
                if (recipe.is_dinner) categories.push('🌙 Abend');
                if (recipe.is_weekend) categories.push('🎉 Wochenende');
                
                // Strukturierte Zutaten anzeigen
                const ingredientsList = recipe.ingredient_list || [];
                const ingredientsPreview = ingredientsList.length > 0 
                    ? `<div class="text-sm text-gray-600 mt-2">
                        ${ingredientsList.slice(0, 3).map(ing => 
                            `${ing.name}${ing.specification ? ' (' + ing.specification + ')' : ''}`
                        ).join(', ')}${ingredientsList.length > 3 ? '...' : ''}
                       </div>`
                    : '<div class="text-sm text-gray-400 mt-2 italic">Keine Zutaten</div>';
                
                const modifierInfo = recipe.modifier_name 
                    ? `<div class="flex items-center gap-1 text-xs text-gray-500 mt-2">
                        ${recipe.modifier_image ? `<span class="text-base">${recipe.modifier_image}</span>` : ''}
                        <span>zuletzt bearbeitet von ${recipe.modifier_name}</span>
                       </div>`
                    : (recipe.creator_name 
                        ? `<div class="flex items-center gap-1 text-xs text-gray-500 mt-2">
                            ${recipe.creator_image ? `<span class="text-base">${recipe.creator_image}</span>` : ''}
                            <span>erstellt von ${recipe.creator_name}</span>
                           </div>`
                        : '');
                
                card.innerHTML = `
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-lg font-bold text-gray-800 flex-1">${recipe.title}</h3>
                        <div class="flex gap-1">
                            <button onclick="editRecipe(${recipe.id}); event.stopPropagation();" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Bearbeiten">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button onclick="confirmDeleteRecipe(${recipe.id}, '${recipe.title.replace(/'/g, "\\'")}'); event.stopPropagation();" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Löschen">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    ${categories.length > 0 ? `<div class="flex flex-wrap gap-2 mb-2">${categories.map(c => `<span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">${c}</span>`).join('')}</div>` : ''}
                    ${ingredientsPreview}
                    ${modifierInfo}
                `;
                
                list.appendChild(card);
            });
        }

        function showAddRecipeModal() {
            editingRecipe = null;
            document.getElementById('recipeModalTitle').textContent = 'Neues Rezept';
            document.getElementById('recipeTitle').value = '';
            document.getElementById('recipeLunch').checked = true;
            document.getElementById('recipeDinner').checked = true;
            document.getElementById('recipeWeekend').checked = false;
            
            // Zutaten-Liste initialisieren
            const ingredientsList = document.getElementById('ingredientsList');
            ingredientsList.innerHTML = '';
            addIngredientField(); // Ein leeres Feld hinzufügen
            
            document.getElementById('recipeModal').classList.remove('hidden');
        }

        function addIngredientField(name = '', specification = '') {
            const ingredientsList = document.getElementById('ingredientsList');
            const index = ingredientsList.children.length;
            
            const div = document.createElement('div');
            div.className = 'flex gap-2 items-start ingredient-row';
            div.innerHTML = `
                <input
                    type="text"
                    class="flex-1 px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:outline-none"
                    placeholder="Zutat (z.B. Spaghetti)"
                    value="${name}"
                    data-field="name"
                />
                <input
                    type="text"
                    class="w-32 px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:outline-none"
                    placeholder="Menge (z.B. 500g)"
                    value="${specification}"
                    data-field="specification"
                />
                <button
                    type="button"
                    onclick="removeIngredientField(this)"
                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            `;
            
            ingredientsList.appendChild(div);
        }

        function removeIngredientField(button) {
            const row = button.closest('.ingredient-row');
            if (row) {
                row.remove();
            }
            
            // Mindestens ein Feld behalten
            const ingredientsList = document.getElementById('ingredientsList');
            if (ingredientsList.children.length === 0) {
                addIngredientField();
            }
        }

        function getIngredientsFromForm() {
            const rows = document.querySelectorAll('.ingredient-row');
            const ingredients = [];
            
            rows.forEach(row => {
                const name = row.querySelector('[data-field="name"]').value.trim();
                const specification = row.querySelector('[data-field="specification"]').value.trim();
                
                if (name) {
                    ingredients.push({
                        name: name,
                        specification: specification || null
                    });
                }
            });
            
            return ingredients;
        }

        function editRecipe(recipeId) {
            const recipe = recipes.find(r => r.id == recipeId);
            if (!recipe) return;
            
            editingRecipe = recipe;
            document.getElementById('recipeModalTitle').textContent = 'Rezept bearbeiten';
            document.getElementById('recipeTitle').value = recipe.title;
            document.getElementById('recipeLunch').checked = recipe.is_lunch;
            document.getElementById('recipeDinner').checked = recipe.is_dinner;
            document.getElementById('recipeWeekend').checked = recipe.is_weekend;
            
            // Zutaten-Liste laden
            const ingredientsList = document.getElementById('ingredientsList');
            ingredientsList.innerHTML = '';
            
            if (recipe.ingredient_list && recipe.ingredient_list.length > 0) {
                recipe.ingredient_list.forEach(ing => {
                    addIngredientField(ing.name, ing.specification || '');
                });
            } else {
                addIngredientField(); // Mindestens ein leeres Feld
            }
            
            document.getElementById('recipeModal').classList.remove('hidden');
        }

        function hideRecipeForm() {
            document.getElementById('recipeModal').classList.add('hidden');
            editingRecipe = null;
        }

        async function saveRecipe() {
            const title = document.getElementById('recipeTitle').value.trim();
            const isLunch = document.getElementById('recipeLunch').checked;
            const isDinner = document.getElementById('recipeDinner').checked;
            const isWeekend = document.getElementById('recipeWeekend').checked;
            const ingredients = getIngredientsFromForm();
            
            if (!title) {
                alert('Bitte geben Sie einen Titel ein');
                return;
            }

            const recipeData = {
                title,
                is_lunch: isLunch,
                is_dinner: isDinner,
                is_weekend: isWeekend,
                created_by: currentUser.id,
                user_id: currentUser.id
            };

            let recipeId;

            if (editingRecipe) {
                const updated = await apiCall(`/update_recipe?id=${editingRecipe.id}`, 'POST', recipeData);
                if (updated && updated.success) {
                    recipeId = editingRecipe.id;
                    const idx = recipes.findIndex(r => r.id == editingRecipe.id);
                    if (idx !== -1) {
                        recipes[idx] = { ...recipes[idx], ...recipeData, last_modified_by: currentUser.id, modifier_name: currentUser.name, modifier_image: currentUser.profile_image };
                    }
                }
            } else {
                const newRecipe = await apiCall('/recipes', 'POST', recipeData);
                if (newRecipe) {
                    recipeId = newRecipe.id;
                    recipes.push({ ...newRecipe, creator_name: currentUser.name, creator_image: currentUser.profile_image, ingredient_list: [] });
                }
            }

            // Zutaten separat speichern
            if (recipeId) {
                await apiCall(`/recipe_ingredients?id=${recipeId}`, 'POST', { ingredients });
            }

            await loadRecipes();
            displayRecipesList();
            hideRecipeForm();
            alert(editingRecipe ? 'Rezept wurde aktualisiert!' : 'Rezept wurde hinzugefügt!');
        }

        function confirmDeleteRecipe(recipeId, title) {
            if (confirm(`Möchten Sie das Rezept "${title}" wirklich löschen?`)) {
                deleteRecipeById(recipeId);
            }
        }

        async function deleteRecipeById(recipeId) {
            const result = await apiCall(`/delete_recipe?id=${recipeId}`, 'POST');
            if (result && result.success) {
                recipes = recipes.filter(r => r.id != recipeId);
                displayRecipesList();
                alert('Rezept wurde gelöscht!');
            }
        }

        // Alte addRecipe Funktion für Schnellzugriff
        async function addRecipe() {
            const title = document.getElementById('recipeTitle').value.trim();
            if (!title) {
                alert('Bitte geben Sie einen Titel ein');
                return;
            }
            const newRecipe = await apiCall('/recipes', 'POST', {
                title: title,
                created_by: currentUser.id
            });
            if (newRecipe) {
                recipes.push(newRecipe);
                hideRecipeForm();
                alert('Rezept wurde hinzugefügt!');
            }
        }

        // ==================== BRING! EXPORT ====================

        async function exportToBring() {
            // Sammle alle Zutaten aus dem aktuellen Wochenplan
            const shoppingList = {};
            
            Object.entries(weekPlan).forEach(([day, meals]) => {
                Object.entries(meals).forEach(([mealType, mealData]) => {
                    if (mealData && mealData.recipe_id) {
                        // Finde das Rezept
                        const recipe = recipes.find(r => r.id == mealData.recipe_id);
                        if (recipe && recipe.ingredient_list) {
                            recipe.ingredient_list.forEach(ingredient => {
                                const name = ingredient.name.toLowerCase().trim();
                                if (shoppingList[name]) {
                                    shoppingList[name].count++;
                                    if (ingredient.specification) {
                                        shoppingList[name].specifications.add(ingredient.specification);
                                    }
                                } else {
                                    shoppingList[name] = {
                                        name: ingredient.name,
                                        count: 1,
                                        specifications: new Set(ingredient.specification ? [ingredient.specification] : [])
                                    };
                                }
                            });
                        }
                    }
                });
            });

            if (Object.keys(shoppingList).length === 0) {
                alert('Keine Zutaten im Wochenplan gefunden!');
                return;
            }

            const listName = BRING_CONFIG.list_name || 'OBI';
            const itemCount = Object.keys(shoppingList).length;
            
            // Formatiere Items für API
            const items = Object.values(shoppingList).map(item => {
                let specification = '';
                
                // Füge Anzahl hinzu
                if (item.count > 1) {
                    specification = `${item.count}x`;
                }
                
                // Füge Spezifikationen hinzu
                if (item.specifications.size > 0) {
                    const specs = Array.from(item.specifications).join(', ');
                    specification = specification ? `${specification} (${specs})` : specs;
                }
                
                return {
                    name: item.name,
                    specification: specification
                };
            });

            // Versuche zuerst direkten API-Export
            try {
                const result = await apiCall('/export_to_bring_direct', 'POST', { items });
                
                if (result && result.success) {
                    alert(`✅ ${itemCount} Artikel erfolgreich zu Bring! Liste "${listName}" hinzugefügt!`);
                    return;
                }
            } catch (error) {
                console.log('Direkter API-Export nicht verfügbar, verwende Deeplink-Methode:', error);
            }

            // Fallback: Deeplink-Methode
            await exportToBringDeeplink(items, listName, itemCount);
        }
        
        async function exportToBringDeeplink(items, listName, itemCount) {
            // Erstelle JSON für Bring! Import
            const bringRecipe = {
                name: `Wochenplan KW ${currentWeek.weekNumber}`,
                source: "Menüplaner",
                servings: 1,
                ingredients: items.map(item => {
                    let text = item.name;
                    if (item.specification) {
                        text += ` (${item.specification})`;
                    }
                    return text;
                })
            };

            // Speichere JSON auf Server
            const result = await apiCall('/save_bring_recipe', 'POST', {
                recipe: bringRecipe,
                week: currentWeek.weekNumber,
                year: currentWeek.year
            });

            if (!result || !result.url) {
                alert('Fehler beim Erstellen des Bring! Exports!');
                return;
            }

            // Erstelle Bring! Deeplink mit Server-URL
            const bringDeeplink = `https://api.getbring.com/rest/bringrecipes/deeplink?url=${encodeURIComponent(result.url)}&source=web`;

            // Zeige Zusammenfassung
            const summary = items.slice(0, 10).map(item => {
                let line = item.name;
                if (item.specification) line += ` (${item.specification})`;
                return `• ${line}`;
            }).join('\n');
            
            const message = `${itemCount} Artikel in Bring! importieren:\n\n${summary}${itemCount > 10 ? '\n...' : ''}\n\n` +
                           `Nach dem Öffnen von Bring! wirst du nach der Liste gefragt.\n` +
                           `Wähle dort "${listName}" aus!`;
            
            if (confirm(message)) {
                // Öffne Bring! Deeplink
                window.open(bringDeeplink, '_blank');
            }
        }
    </script>
</body>
</html>
