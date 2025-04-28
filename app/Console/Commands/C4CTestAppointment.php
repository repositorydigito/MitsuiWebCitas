<?php

namespace App\Console\Commands;

use App\Services\C4C\AppointmentService;
use App\Services\C4C\MockAppointmentService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class C4CTestAppointment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:test-appointment
                            {action=create : Action to perform (create, update, delete)}
                            {--uuid= : UUID of the appointment (required for update and delete)}
                            {--mock : Use mock service instead of real service}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test C4C appointment service';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $useMock = $this->option('mock');
        
        // Determine which service to use
        if ($useMock) {
            $this->info("Using mock appointment service");
            $service = new MockAppointmentService();
        } else {
            $this->info("Using real appointment service");
            $service = new AppointmentService();
        }
        
        // Execute the requested action
        switch ($action) {
            case 'create':
                $this->createAppointment($service);
                break;
            case 'update':
                $this->updateAppointment($service);
                break;
            case 'delete':
                $this->deleteAppointment($service);
                break;
            default:
                $this->error("Invalid action: {$action}. Valid actions are: create, update, delete");
                return 1;
        }
        
        return 0;
    }
    
    /**
     * Create a test appointment.
     *
     * @param AppointmentService|MockAppointmentService $service
     */
    protected function createAppointment($service)
    {
        $this->info("Creating test appointment...");
        
        // Get customer ID from user input or use default
        $customerId = $this->ask('Enter customer ID (BusinessPartnerInternalID)', '1270000347');
        
        // Get start date and time
        $startDate = $this->ask('Enter start date (YYYY-MM-DD)', Carbon::now()->addDay()->format('Y-m-d'));
        $startTime = $this->ask('Enter start time (HH:MM)', '13:30');
        $startDateTime = $startDate . ' ' . $startTime;
        
        // Calculate end time (15 minutes later)
        $endDateTime = Carbon::parse($startDateTime)->addMinutes(15)->format('Y-m-d H:i');
        $this->info("End date/time: {$endDateTime}");
        
        // Get other appointment details
        $vehiclePlate = $this->ask('Enter vehicle plate', 'XXX-123');
        $centerId = $this->ask('Enter center ID', 'M013');
        $notes = $this->ask('Enter appointment notes', 'Test appointment created via command line');
        $customerName = $this->ask('Enter customer name', 'Test Customer');
        $express = $this->confirm('Is this an express appointment?', false);
        
        // Prepare appointment data
        $appointmentData = [
            'customer_id' => $customerId,
            'start_date' => $startDateTime,
            'end_date' => $endDateTime,
            'vehicle_plate' => $vehiclePlate,
            'center_id' => $centerId,
            'notes' => $notes,
            'customer_name' => $customerName,
            'express' => $express ? 'true' : 'false',
        ];
        
        // Create the appointment
        $result = $service->create($appointmentData);
        
        // Display the result
        if ($result['success']) {
            $this->info("Appointment created successfully!");
            $this->info("UUID: " . $result['data']['uuid']);
            $this->info("ID: " . $result['data']['id']);
            $this->info("Change State ID: " . $result['data']['change_state_id']);
            
            // Save the UUID for later use
            $this->saveUuid($result['data']['uuid']);
        } else {
            $this->error("Failed to create appointment: " . $result['error']);
        }
    }
    
    /**
     * Update a test appointment.
     *
     * @param AppointmentService|MockAppointmentService $service
     */
    protected function updateAppointment($service)
    {
        $this->info("Updating test appointment...");
        
        // Get UUID from option or from saved file
        $uuid = $this->option('uuid') ?: $this->getSavedUuid();
        
        if (empty($uuid)) {
            $this->error("UUID is required for update. Use --uuid option or create an appointment first.");
            return;
        }
        
        $this->info("Using UUID: {$uuid}");
        
        // Get update options
        $updateStatus = $this->confirm('Update appointment status?', true);
        $status = null;
        $appointmentStatus = null;
        
        if ($updateStatus) {
            $status = $this->choice('Select lifecycle status', [
                '1' => 'Open',
                '2' => 'In Process',
                '3' => 'Completed',
                '4' => 'Cancelled'
            ], '2');
            
            $appointmentStatus = $this->choice('Select appointment status', [
                '1' => 'Generated',
                '2' => 'Confirmed',
                '3' => 'In Progress',
                '4' => 'Deferred',
                '5' => 'Completed',
                '6' => 'Deleted'
            ], '3');
        }
        
        $updateDates = $this->confirm('Update appointment dates?', false);
        $startDateTime = null;
        $endDateTime = null;
        
        if ($updateDates) {
            // Get start date and time
            $startDate = $this->ask('Enter new start date (YYYY-MM-DD)', Carbon::now()->addDay()->format('Y-m-d'));
            $startTime = $this->ask('Enter new start time (HH:MM)', '14:30');
            $startDateTime = $startDate . ' ' . $startTime;
            
            // Calculate end time (15 minutes later)
            $endDateTime = Carbon::parse($startDateTime)->addMinutes(15)->format('Y-m-d H:i');
            $this->info("New end date/time: {$endDateTime}");
        }
        
        // Prepare update data
        $updateData = [];
        
        if ($updateStatus) {
            $updateData['status'] = $status;
            $updateData['appointment_status'] = $appointmentStatus;
        }
        
        if ($updateDates) {
            $updateData['start_date'] = $startDateTime;
            $updateData['end_date'] = $endDateTime;
        }
        
        // Update the appointment
        $result = $service->update($uuid, $updateData);
        
        // Display the result
        if ($result['success']) {
            $this->info("Appointment updated successfully!");
            $this->info("UUID: " . $result['data']['uuid']);
            $this->info("ID: " . $result['data']['id']);
            $this->info("Change State ID: " . $result['data']['change_state_id']);
        } else {
            $this->error("Failed to update appointment: " . $result['error']);
        }
    }
    
    /**
     * Delete a test appointment.
     *
     * @param AppointmentService|MockAppointmentService $service
     */
    protected function deleteAppointment($service)
    {
        $this->info("Deleting test appointment...");
        
        // Get UUID from option or from saved file
        $uuid = $this->option('uuid') ?: $this->getSavedUuid();
        
        if (empty($uuid)) {
            $this->error("UUID is required for delete. Use --uuid option or create an appointment first.");
            return;
        }
        
        $this->info("Using UUID: {$uuid}");
        
        // Confirm deletion
        if (!$this->confirm("Are you sure you want to delete appointment {$uuid}?", true)) {
            $this->info("Deletion cancelled.");
            return;
        }
        
        // Delete the appointment
        $result = $service->delete($uuid);
        
        // Display the result
        if ($result['success']) {
            $this->info("Appointment deleted successfully!");
            $this->info("UUID: " . $result['data']['uuid']);
            $this->info("ID: " . $result['data']['id']);
            $this->info("Change State ID: " . $result['data']['change_state_id']);
            
            // Remove the saved UUID
            $this->removeSavedUuid();
        } else {
            $this->error("Failed to delete appointment: " . $result['error']);
        }
    }
    
    /**
     * Save UUID to a temporary file.
     *
     * @param string $uuid
     */
    protected function saveUuid($uuid)
    {
        $filePath = storage_path('app/c4c_appointment_uuid.txt');
        file_put_contents($filePath, $uuid);
        $this->info("UUID saved for later use: {$uuid}");
    }
    
    /**
     * Get saved UUID from temporary file.
     *
     * @return string|null
     */
    protected function getSavedUuid()
    {
        $filePath = storage_path('app/c4c_appointment_uuid.txt');
        
        if (file_exists($filePath)) {
            return trim(file_get_contents($filePath));
        }
        
        return null;
    }
    
    /**
     * Remove saved UUID file.
     */
    protected function removeSavedUuid()
    {
        $filePath = storage_path('app/c4c_appointment_uuid.txt');
        
        if (file_exists($filePath)) {
            unlink($filePath);
            $this->info("Saved UUID removed.");
        }
    }
}
