<?php

use Colame\Staff\Http\Controllers\Web\StaffController as WebStaffController;
use Colame\Staff\Http\Controllers\Web\RoleController as WebRoleController;
use Colame\Staff\Http\Controllers\Web\ScheduleController as WebScheduleController;
use Colame\Staff\Http\Controllers\Web\AttendanceController as WebAttendanceController;
use Colame\Staff\Http\Controllers\Web\ReportController as WebReportController;
use Colame\Staff\Http\Controllers\Api\StaffController as ApiStaffController;
use Colame\Staff\Http\Controllers\Api\RoleController as ApiRoleController;
use Colame\Staff\Http\Controllers\Api\ScheduleController as ApiScheduleController;
use Colame\Staff\Http\Controllers\Api\AttendanceController as ApiAttendanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Staff Module Routes
|--------------------------------------------------------------------------
|
| Here are all routes for the Staff module, separated by Web and API
|
*/

// Web Routes (Inertia)
Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::prefix('staff')->name('staff.')->group(function () {
        // Staff management
        Route::get('/', [WebStaffController::class, 'index'])->name('index');
        Route::get('/create', [WebStaffController::class, 'create'])->name('create');
        Route::post('/', [WebStaffController::class, 'store'])->name('store');
        
        // Schedule and attendance special routes (before dynamic routes)
        Route::get('/schedule', [WebScheduleController::class, 'index'])->name('schedule.index');
        Route::get('/attendance', [WebAttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/reports', [WebReportController::class, 'index'])->name('reports.index');
        
        // Roles management
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [WebRoleController::class, 'index'])->name('index');
            Route::get('/create', [WebRoleController::class, 'create'])->name('create');
            Route::post('/', [WebRoleController::class, 'store'])->name('store');
            Route::get('/{role}', [WebRoleController::class, 'show'])->name('show')->where('role', '[0-9]+');
            Route::get('/{role}/edit', [WebRoleController::class, 'edit'])->name('edit')->where('role', '[0-9]+');
            Route::put('/{role}', [WebRoleController::class, 'update'])->name('update')->where('role', '[0-9]+');
            Route::delete('/{role}', [WebRoleController::class, 'destroy'])->name('destroy')->where('role', '[0-9]+');
            Route::post('/{role}/permissions', [WebRoleController::class, 'updatePermissions'])->name('permissions')->where('role', '[0-9]+');
        });
        
        // Single staff member operations (with numeric constraint)
        Route::get('/{staff}', [WebStaffController::class, 'show'])->name('show')->where('staff', '[0-9]+');
        Route::get('/{staff}/edit', [WebStaffController::class, 'edit'])->name('edit')->where('staff', '[0-9]+');
        Route::put('/{staff}', [WebStaffController::class, 'update'])->name('update')->where('staff', '[0-9]+');
        Route::delete('/{staff}', [WebStaffController::class, 'destroy'])->name('destroy')->where('staff', '[0-9]+');
        
        // Staff member specific actions
        Route::post('/{staff}/assign-role', [WebStaffController::class, 'assignRole'])->name('assign-role')->where('staff', '[0-9]+');
        Route::post('/{staff}/remove-role', [WebStaffController::class, 'removeRole'])->name('remove-role')->where('staff', '[0-9]+');
        Route::post('/{staff}/activate', [WebStaffController::class, 'activate'])->name('activate')->where('staff', '[0-9]+');
        Route::post('/{staff}/deactivate', [WebStaffController::class, 'deactivate'])->name('deactivate')->where('staff', '[0-9]+');
    });
});

// API Routes
Route::middleware(['api', 'auth:sanctum'])->prefix('api/v1')->group(function () {
    Route::prefix('staff')->name('api.staff.')->group(function () {
        // Basic CRUD
        Route::get('/', [ApiStaffController::class, 'index'])->name('index');
        Route::post('/', [ApiStaffController::class, 'store'])->name('store');
        Route::get('/{staff}', [ApiStaffController::class, 'show'])->name('show');
        Route::put('/{staff}', [ApiStaffController::class, 'update'])->name('update');
        Route::delete('/{staff}', [ApiStaffController::class, 'destroy'])->name('destroy');
        
        // Role management
        Route::post('/{staff}/roles', [ApiStaffController::class, 'assignRole'])->name('assign-role');
        Route::delete('/{staff}/roles/{role}', [ApiStaffController::class, 'removeRole'])->name('remove-role');
        
        // Attendance
        Route::post('/clock-in', [ApiAttendanceController::class, 'clockIn'])->name('clock-in');
        Route::post('/clock-out', [ApiAttendanceController::class, 'clockOut'])->name('clock-out');
        Route::get('/attendance/current', [ApiAttendanceController::class, 'current'])->name('attendance.current');
        
        // Schedule
        Route::get('/schedule', [ApiScheduleController::class, 'index'])->name('schedule.index');
        Route::get('/schedule/my-shifts', [ApiScheduleController::class, 'myShifts'])->name('schedule.my-shifts');
        
        // Roles
        Route::get('/roles', [ApiRoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/{role}', [ApiRoleController::class, 'show'])->name('roles.show');
    });
});