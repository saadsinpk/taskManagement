<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RoleHasPremissionController;
use App\Http\Controllers\TaskManagementController;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\TaskClanderController;
use App\Http\Controllers\TaskManagementStatusController;
use App\Http\Controllers\OrderManagementController;
use App\Http\Controllers\OrderManagementStatusController;
use App\Http\Controllers\GeneralSettingController;
use App\Http\Controllers\HelpCenterController;
use App\Http\Controllers\ChatsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\ProjectOverviewController;
use App\Http\Controllers\ToDoListPrivatController;
use App\Http\Controllers\StickyNotesController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationSettingController;
use App\Http\Controllers\ReportingController;
use App\Http\Middleware\AuthenticateApi;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Login
Route::post('/login', [UserController::class, 'login']);

Route::middleware(['verify.api.token'])->group(function () {

    //Dashboard
    Route::get('/dashboard', [DashboardController::class, 'dashboard']);

    //Users
    Route::middleware(['check.role'])->group(function () {
        Route::post('/userCreate', [UserController::class, 'userCreate'])->name('users.create');
        Route::get('/userList', [UserController::class, 'userList'])->name('users.list');
        Route::get('/userEdit/{id}', [UserController::class, 'userEdit'])->name('users.edit');
        Route::get('/userDelete/{id}', [UserController::class, 'userDelete'])->name('users.delete');
    });
    Route::post('/userUpdate', [UserController::class, 'userUpdate']);

    //Profiles
    Route::get('/userProfile', [UserController::class, 'userProfile']);
    Route::post('/userProfileUpdate', [UserController::class, 'userProfileUpdate']);

    //Roles
    Route::middleware(['check.role'])->group(function () {
        Route::post('/roleCreate', [RoleController::class, 'create']);
        Route::get('/roleList', [RoleController::class, 'list']);
        Route::get('/roleEdit/{id}', [RoleController::class, 'edit']);
        Route::get('/roleDelete/{id}/{newID}', [RoleController::class, 'delete']);
    });
    Route::post('/roleUpdate', [RoleController::class, 'update']);

    //Roles and Premission
    Route::get('/premissionView/{id}', [RoleHasPremissionController::class, 'premissionView']);
    Route::get('/premissionModuleView/{id}/{model}', [RoleHasPremissionController::class, 'premissionModuleView']);
    Route::post('/premissionUpdate', [RoleHasPremissionController::class, 'premissionUpdate']);

    //Task Management
    //Task
    Route::get('/taskCreateData', [TaskManagementController::class, 'taskCreateData']);
    Route::middleware(['check.role'])->group(function () {
        Route::post('/taskCreate', [TaskManagementController::class, 'taskCreate']);
        Route::get('/taskList', [TaskManagementController::class, 'taskList']);
        Route::get('/taskEdit/{id}', [TaskManagementController::class, 'taskEdit']);

        Route::get('/taskDelete/{id}', [TaskManagementController::class, 'taskDelete']);
    });
    Route::get('/taskView/{id}', [TaskManagementController::class, 'taskView']);
    Route::post('/taskUpdate', [TaskManagementController::class, 'taskUpdate']);

    //Kanban
    Route::middleware(['check.role'])->group(function () {
        Route::get('/kanbanView', [KanbanController::class, 'kanbanView']);
    });
    Route::post('/kanbanUpdate', [KanbanController::class, 'kanbanUpdate']);

    //Clanders
    Route::get('/calendarList', [TaskClanderController::class, 'calendarList']);
    Route::get('/clanderViewTask/{id}', [TaskClanderController::class, 'clanderViewTask']);
    Route::post('/calendarListMonth', [TaskClanderController::class, 'calendarListMonth']);
    Route::post('/calendarListWeek', [TaskClanderController::class, 'calendarListWeek']);
    Route::post('/calendarListDay', [TaskClanderController::class, 'calendarListDay']);

    //Status
    Route::middleware(['check.role'])->group(function () {
        Route::post('/statusCreate', [TaskManagementStatusController::class, 'statusCreate']);
        Route::get('/statusList', [TaskManagementStatusController::class, 'statusList']);
        Route::get('/statusEdit/{id}', [TaskManagementStatusController::class, 'statusEdit']);
        Route::get('/statusDelete/{id}/{newID}', [TaskManagementStatusController::class, 'statusDelete']);
    });
    Route::post('/statusUpdate', [TaskManagementStatusController::class, 'statusUpdate']);

    //OrderManagement
    //Order
    Route::middleware(['check.role'])->group(function () {
        Route::post('/orderCreate', [OrderManagementController::class, 'orderCreate']);
        Route::get('/orderList', [OrderManagementController::class, 'orderList']);
        Route::get('/orderEdit/{id}', [OrderManagementController::class, 'orderEdit']);
        Route::get('/orderDelete/{id}', [OrderManagementController::class, 'orderDelete']);
    });
    Route::post('/orderUpdate', [OrderManagementController::class, 'orderUpdate']);

    //Status
    Route::middleware(['check.role'])->group(function () {
        Route::post('/orderStatusCreate', [OrderManagementStatusController::class, 'orderStatusCreate']);
        Route::get('/orderStatusList', [OrderManagementStatusController::class, 'orderStatusList']);
        Route::get('/orderStatusEdit/{id}', [OrderManagementStatusController::class, 'orderStatusEdit']);
        Route::get('/orderStatusDelete/{id}/{newID}', [OrderManagementStatusController::class, 'orderStatusDelete']);
    });
    Route::post('/orderStatusUpdate', [OrderManagementStatusController::class, 'orderStatusUpdate']);

    //GeneralSetting
    Route::get('/generalSetting', [GeneralSettingController::class, 'generalSetting']);
    Route::post('/generalSettingUpdate', [GeneralSettingController::class, 'generalSettingUpdate']);

    //Help Center
    Route::middleware(['check.role'])->group(function () {
        Route::post('/helpCenterCreate', [HelpCenterController::class, 'helpCenterCreate']);
        Route::get('/helpCenterList', [HelpCenterController::class, 'helpCenterList']);
        Route::get('/helpCenterEdit/{id}', [HelpCenterController::class, 'helpCenterEdit']);
        Route::get('/helpCenterDelete/{id}', [HelpCenterController::class, 'helpCenterDelete']);
    });
    Route::post('/helpCenterUpdate', [HelpCenterController::class, 'helpCenterUpdate']);

    //API Integration
    Route::post('/apiConfiguration', [ApiController::class, 'apiConfiguration']);

    //Notifaction
    Route::get('/notifications', [NotificationController::class, 'index'])->middleware('auth:api');
    Route::get('/notificationsList', [NotificationController::class, 'notificationsList']);

    //Notifaction Setting
    Route::post('/notificationSettingUpdate', [NotificationSettingController::class, 'notificationSettingUpdate']);
    Route::get('/notificationSettingView', [NotificationSettingController::class, 'notificationSettingView']);

    //Communication Tools
    //Chats
    Route::get('/chat', [ChatsController::class, 'chat']);
    Route::get('/messageRecevied', [ChatsController::class, 'messageRecevied']);
    Route::post('/messageSend', [ChatsController::class, 'messageSend']);
    Route::get('/messageHistory/{received_id}', [ChatsController::class, 'messageHistory']);
    Route::post('/messageHistoryLoder', [ChatsController::class, 'messageHistoryLoder']);
    Route::get('/messageConversation', [ChatsController::class, 'messageConversation']);
    Broadcast::routes(['prefix' => 'api', 'middleware' => ['auth:api']]);

    //StickyNotes
    Route::post('/stickyNotesUpdate', [StickyNotesController::class, 'stickyNotesUpdate']);

    //TodoListPrivate
    Route::post('/todoCreate', [ToDoListPrivatController::class, 'todoCreate']);
    Route::get('/todoList', [ToDoListPrivatController::class, 'todoList']);
    Route::get('/todoEdit/{id}', [ToDoListPrivatController::class, 'todoEdit']);
    Route::post('/todoUpdate', [ToDoListPrivatController::class, 'todoUpdate']);
    Route::post('/todoDelete', [ToDoListPrivatController::class, 'todoDelete']);

    //ProjectOverview
    Route::post('/projectOverviewUpdate', [ProjectOverViewController::class, 'projectOverviewUpdate']);
    Route::get('/projectOverviewList', [ProjectOverViewController::class, 'projectOverviewList']);

    //AuthUserData
    Route::get('/authUserData', [UserController::class, 'authUserData']);

    //Reporting
    Route::get('/reportYear', [ReportingController::class, 'reportYear']);
    Route::get('/reportMonth', [ReportingController::class, 'reportMonth']);
    Route::get('/reportWeek', [ReportingController::class, 'reportWeek']);
    Route::get('/reportDay', [ReportingController::class, 'reportDay']);
    Route::post('/fetchDataBetweenDates', [ReportingController::class, 'fetchDataBetweenDates']);

    //DropDown
    Route::get('/userListDropdown', [UserController::class, 'userList']);
    Route::get('/roleListDropdown', [RoleController::class, 'list']);
    Route::get('/taskStatusListDropdown', [TaskManagementStatusController::class, 'statusList']);
    Route::get('/orderStatusListDropdown', [OrderManagementStatusController::class, 'orderStatusList']);
    Route::get('/orderListDropdown', [OrderManagementController::class, 'orderListDropdown']);
});
