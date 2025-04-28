<?php

namespace App\Console\Commands;

use App\Services\C4C\AppointmentQueryService;
use App\Services\C4C\MockAppointmentQueryService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class C4CTestAppointmentQuery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:test-appointment-query
                            {action=pending : Action to perform (pending, all, vehicle, center, date)}
                            {--customer_id= : Customer ID (required for pending and all)}
                            {--vehicle_plate= : Vehicle plate (required for vehicle)}
                            {--center_id= : Center ID (required for center)}
                            {--start_date= : Start date (required for date)}
                            {--end_date= : End date (required for date)}
                            {--status= : Appointment status}
                            {--limit=10 : Maximum number of results}
                            {--mock : Use mock service instead of real service}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test C4C appointment query service';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $useMock = $this->option('mock');
        
        // Determine which service to use
        if ($useMock) {
            $this->info("Using mock appointment query service");
            $service = new MockAppointmentQueryService();
        } else {
            $this->info("Using real appointment query service");
            $service = new AppointmentQueryService();
        }
        
        // Execute the requested action
        switch ($action) {
            case 'pending':
                $this->getPendingAppointments($service);
                break;
            case 'all':
                $this->getAllAppointments($service);
                break;
            case 'vehicle':
                $this->getAppointmentsByVehiclePlate($service);
                break;
            case 'center':
                $this->getAppointmentsByCenter($service);
                break;
            case 'date':
                $this->getAppointmentsByDateRange($service);
                break;
            default:
                $this->error("Invalid action: {$action}. Valid actions are: pending, all, vehicle, center, date");
                return 1;
        }
        
        return 0;
    }
    
    /**
     * Get pending appointments for a customer.
     *
     * @param AppointmentQueryService|MockAppointmentQueryService $service
     */
    protected function getPendingAppointments($service)
    {
        $this->info("Getting pending appointments...");
        
        // Get customer ID
        $customerId = $this->option('customer_id');
        if (empty($customerId)) {
            $customerId = $this->ask('Enter customer ID', '1270002726');
        }
        
        // Get options
        $options = $this->getCommonOptions();
        
        // Get pending appointments
        $result = $service->getPendingAppointments($customerId, $options);
        
        // Display the result
        $this->displayResult($result);
    }
    
    /**
     * Get all appointments for a customer.
     *
     * @param AppointmentQueryService|MockAppointmentQueryService $service
     */
    protected function getAllAppointments($service)
    {
        $this->info("Getting all appointments...");
        
        // Get customer ID
        $customerId = $this->option('customer_id');
        if (empty($customerId)) {
            $customerId = $this->ask('Enter customer ID', '1270002726');
        }
        
        // Get options
        $options = $this->getCommonOptions();
        
        // Get all appointments
        $result = $service->getAllAppointments($customerId, $options);
        
        // Display the result
        $this->displayResult($result);
    }
    
    /**
     * Get appointments by vehicle plate.
     *
     * @param AppointmentQueryService|MockAppointmentQueryService $service
     */
    protected function getAppointmentsByVehiclePlate($service)
    {
        $this->info("Getting appointments by vehicle plate...");
        
        // Get vehicle plate
        $vehiclePlate = $this->option('vehicle_plate');
        if (empty($vehiclePlate)) {
            $vehiclePlate = $this->ask('Enter vehicle plate', 'APP-001');
        }
        
        // Get options
        $options = $this->getCommonOptions();
        
        // Get appointments by vehicle plate
        $result = $service->getAppointmentsByVehiclePlate($vehiclePlate, $options);
        
        // Display the result
        $this->displayResult($result);
    }
    
    /**
     * Get appointments by center.
     *
     * @param AppointmentQueryService|MockAppointmentQueryService $service
     */
    protected function getAppointmentsByCenter($service)
    {
        $this->info("Getting appointments by center...");
        
        // Get center ID
        $centerId = $this->option('center_id');
        if (empty($centerId)) {
            $centerId = $this->ask('Enter center ID', 'M013');
        }
        
        // Get options
        $options = $this->getCommonOptions();
        
        // Get appointments by center
        $result = $service->getAppointmentsByCenter($centerId, $options);
        
        // Display the result
        $this->displayResult($result);
    }
    
    /**
     * Get appointments by date range.
     *
     * @param AppointmentQueryService|MockAppointmentQueryService $service
     */
    protected function getAppointmentsByDateRange($service)
    {
        $this->info("Getting appointments by date range...");
        
        // Get start date
        $startDate = $this->option('start_date');
        if (empty($startDate)) {
            $startDate = $this->ask('Enter start date (YYYY-MM-DD)', Carbon::now()->format('Y-m-d'));
        }
        
        // Get end date
        $endDate = $this->option('end_date');
        if (empty($endDate)) {
            $endDate = $this->ask('Enter end date (YYYY-MM-DD)', Carbon::now()->addDays(7)->format('Y-m-d'));
        }
        
        // Get options
        $options = $this->getCommonOptions();
        
        // Get appointments by date range
        $result = $service->getAppointmentsByDateRange($startDate, $endDate, $options);
        
        // Display the result
        $this->displayResult($result);
    }
    
    /**
     * Get common options for all queries.
     *
     * @return array
     */
    protected function getCommonOptions()
    {
        $options = [];
        
        // Get status
        $status = $this->option('status');
        if (!empty($status)) {
            $options['status'] = $status;
        }
        
        // Get limit
        $limit = $this->option('limit');
        if (!empty($limit)) {
            $options['limit'] = $limit;
        }
        
        // Get customer ID (for vehicle, center, and date queries)
        $customerId = $this->option('customer_id');
        if (!empty($customerId)) {
            $options['customer_id'] = $customerId;
        }
        
        // Get vehicle plate (for customer, center, and date queries)
        $vehiclePlate = $this->option('vehicle_plate');
        if (!empty($vehiclePlate)) {
            $options['vehicle_plate'] = $vehiclePlate;
        }
        
        // Get center ID (for customer, vehicle, and date queries)
        $centerId = $this->option('center_id');
        if (!empty($centerId)) {
            $options['center_id'] = $centerId;
        }
        
        // Get date range (for customer, vehicle, and center queries)
        $startDate = $this->option('start_date');
        $endDate = $this->option('end_date');
        if (!empty($startDate) && !empty($endDate)) {
            $options['start_date'] = $startDate;
            $options['end_date'] = $endDate;
        }
        
        return $options;
    }
    
    /**
     * Display the result of a query.
     *
     * @param array $result
     */
    protected function displayResult($result)
    {
        if ($result['success']) {
            $appointments = $result['data'];
            $count = $result['count'];
            
            $this->info("Found {$count} appointments");
            
            if ($count > 0) {
                $this->info("\nAppointment details:");
                
                foreach ($appointments as $index => $appointment) {
                    $this->info("\n--- Appointment " . ($index + 1) . " ---");
                    
                    // Basic information
                    $this->info("<fg=green;options=bold>Basic Information:</>");
                    $this->info("ID: " . $appointment['id']);
                    $this->info("UUID: " . $appointment['uuid']);
                    $this->info("Subject: " . $appointment['subject']);
                    $this->info("Location: " . $appointment['location']);
                    
                    // Status information
                    $this->info("\n<fg=green;options=bold>Status Information:</>");
                    $this->info("Lifecycle Status: " . $appointment['status']['lifecycle_code'] . " - " . $appointment['status']['lifecycle_name']);
                    $this->info("Appointment Status: " . $appointment['status']['appointment_code'] . " - " . $appointment['status']['appointment_name']);
                    $this->info("Priority: " . $appointment['status']['priority_code'] . " - " . $appointment['status']['priority_name']);
                    
                    // Date information
                    $this->info("\n<fg=green;options=bold>Date Information:</>");
                    $this->info("Scheduled Start: " . $appointment['dates']['scheduled_start']);
                    $this->info("Scheduled End: " . $appointment['dates']['scheduled_end']);
                    
                    if (!empty($appointment['dates']['actual_start'])) {
                        $this->info("Actual Start: " . $appointment['dates']['actual_start']);
                    }
                    
                    if (!empty($appointment['dates']['actual_end'])) {
                        $this->info("Actual End: " . $appointment['dates']['actual_end']);
                    }
                    
                    // Vehicle information
                    $this->info("\n<fg=green;options=bold>Vehicle Information:</>");
                    $this->info("Plate: " . $appointment['vehicle']['plate']);
                    $this->info("Model: " . $appointment['vehicle']['model_description']);
                    $this->info("VIN: " . $appointment['vehicle']['vin']);
                    $this->info("Year: " . $appointment['vehicle']['year']);
                    $this->info("Mileage: " . $appointment['vehicle']['mileage']);
                    
                    // Customer information
                    $this->info("\n<fg=green;options=bold>Customer Information:</>");
                    $this->info("ID: " . $appointment['customer']['id']);
                    $this->info("Phone: " . $appointment['customer']['phone']);
                    $this->info("Fixed Phone: " . $appointment['customer']['fixed_phone']);
                    $this->info("Address: " . $appointment['customer']['address']);
                    
                    // Center information
                    $this->info("\n<fg=green;options=bold>Center Information:</>");
                    $this->info("ID: " . $appointment['center']['id']);
                    $this->info("Description: " . $appointment['center']['description']);
                    
                    // Taxi information
                    if (isset($appointment['taxi']) && isset($appointment['taxi']['requested']) && $appointment['taxi']['requested'] == '1') {
                        $this->info("\n<fg=green;options=bold>Taxi Information:</>");
                        $this->info("Requested: Yes");
                        $this->info("Description: " . $appointment['taxi']['description']);
                    }
                }
            }
            
            // Display processing conditions
            if (isset($result['processing_conditions'])) {
                $this->info("\n<fg=yellow;options=bold>Processing Conditions:</>");
                $this->info("Returned Query Hits: " . $result['processing_conditions']['returned_query_hits_number_value']);
                $this->info("More Hits Available: " . ($result['processing_conditions']['more_hits_available_indicator'] ? 'Yes' : 'No'));
            }
        } else {
            $this->error("Failed to get appointments: " . $result['error']);
        }
    }
}
