<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\models\Position;
use common\models\Application;
use common\models\Organization;
use common\models\User;

class TestController extends Controller
{
    public function actionTestDelete($id = 1)
    {
        echo "Testing delete functionality...\n";
        
        try {
            // Find position
            $position = Position::findOne($id);
            if (!$position) {
                echo "Position not found with ID: $id\n";
                return;
            }
            
            echo "Found position: ID={$position->id}, Title={$position->title}\n";
            echo "Organization ID: {$position->organization_id}\n";
            
            // Check applications
            $applications = $position->getApplications()->all();
            echo "Applications count: " . count($applications) . "\n";
            
            // Attempt to delete
            echo "Attempting to delete position...\n";
            if ($position->delete()) {
                echo "Position deleted successfully!\n";
            } else {
                echo "Failed to delete position\n";
            }
            
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
    
    public function actionTestDatabase()
    {
        echo "Testing database connection and tables...\n";
        
        try {
            // Test connection
            $connection = Yii::$app->db;
            $connection->open();
            echo "Database connection: OK\n";
            
            // Test tables
            $positionsCount = Position::find()->count();
            echo "Positions count: $positionsCount\n";
            
            $applicationsCount = Application::find()->count();
            echo "Applications count: $applicationsCount\n";
            
            $organizationsCount = Organization::find()->count();
            echo "Organizations count: $organizationsCount\n";
            
            $usersCount = User::find()->count();
            echo "Users count: $usersCount\n";
            
            // Test foreign key constraints
            echo "\nTesting foreign key constraints...\n";
            
            $position = Position::findOne(1);
            if ($position) {
                $applications = $position->getApplications()->all();
                echo "Applications for position 1: " . count($applications) . "\n";
                
                $organization = $position->getOrganization()->one();
                echo "Organization for position 1: " . ($organization ? $organization->name : 'Not found') . "\n";
            }
            
            echo "\nDatabase test completed successfully!\n";
            
        } catch (\Exception $e) {
            echo "Database test failed: " . $e->getMessage() . "\n";
        }
    }
    
    public function actionTestWebDelete($id = 1)
    {
        echo "Testing web delete simulation...\n";
        
        try {
            // Simulate web request
            $position = Position::findOne($id);
            if (!$position) {
                echo "Position not found\n";
                return;
            }
            
            echo "Position found: {$position->title}\n";
            
            // Check if position has applications
            $applicationsCount = $position->getApplications()->count();
            echo "Applications count: $applicationsCount\n";
            
            if ($applicationsCount > 0) {
                echo "Cannot delete position with applications\n";
                return;
            }
            
            // Simulate delete
            echo "Simulating delete...\n";
            if ($position->delete()) {
                echo "Position deleted successfully!\n";
            } else {
                echo "Failed to delete position\n";
            }
            
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
} 