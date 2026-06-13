<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\models\User;
use common\models\Student;
use common\models\Organization;
use common\models\Position;
use common\models\Application;
use common\models\Notification;
use common\models\Admin;

class DataSeederController extends Controller
{
    public function actionIndex()
    {
        echo "🌱 Starting comprehensive data seeding...\n\n";
        
        // Clear existing data (optional - be careful in production)
        // $this->clearData();
        
        // Create users and their profiles
        $this->createUsers();
        $this->createOrganizations();
        $this->createPositions();
        $this->createApplications();
        $this->createNotifications();
        
        echo "\n✅ Data seeding completed successfully!\n";
        echo "\n📋 Login Credentials:\n";
        echo "Admin: admin/admin123\n";
        echo "Students: student1/password123, student2/password123, student3/password123\n";
        echo "Organizations: org1/password123, org2/password123, org3/password123\n";
    }
    
    private function createUsers()
    {
        echo "👥 Creating users and student profiles...\n";
        
        $students = [
            [
                'username' => 'student1', 
                'email' => 'student1@example.com', 
                'university' => 'University of Dar es Salaam', 
                'field_of_study' => 'Computer Science',
                'student_id' => '2020/CS/001'
            ],
            [
                'username' => 'student2', 
                'email' => 'student2@example.com', 
                'university' => 'Ardhi University', 
                'field_of_study' => 'Marketing',
                'student_id' => '2020/MK/002'
            ],
            [
                'username' => 'student3', 
                'email' => 'student3@example.com', 
                'university' => 'University of Dodoma', 
                'field_of_study' => 'Statistics',
                'student_id' => '2020/ST/003'
            ],
        ];
        
        foreach ($students as $studentData) {
            $user = User::findOne(['username' => $studentData['username']]);
            if (!$user) {
                $user = new User();
                $user->username = $studentData['username'];
                $user->email = $studentData['email'];
                $user->setPassword('password123');
                $user->generateAuthKey();
                $user->role = 'student';
                $user->status = User::STATUS_ACTIVE;
                $user->created_at = time();
                $user->updated_at = time();
                $user->save();
                
                $student = new Student();
                $student->user_id = $user->id;
                $student->student_id = $studentData['student_id'];
                $student->university = $studentData['university'];
                $student->personal_statement = 'I am studying ' . $studentData['field_of_study'] . ' and looking for practical training opportunities to enhance my skills.';
                $student->save();
                
                echo "  ✅ Created student: {$studentData['username']} (ID: {$user->id})\n";
            } else {
                echo "  ⚠️  Student already exists: {$studentData['username']}\n";
            }
        }
    }
    
    private function createOrganizations()
    {
        echo "\n🏢 Creating organizations...\n";
        
        $organizations = [
            [
                'username' => 'org1', 
                'email' => 'org1@example.com', 
                'name' => 'TechCorp Solutions Ltd', 
                'location' => 'Dar es Salaam',
                'description' => 'Leading technology company specializing in software development and digital solutions.',
                'website' => 'https://techcorp.co.tz'
            ],
            [
                'username' => 'org2', 
                'email' => 'org2@example.com', 
                'name' => 'Green Marketing Agency', 
                'location' => 'Arusha',
                'description' => 'Full-service marketing agency focused on sustainable and eco-friendly campaigns.',
                'website' => 'https://greenmarketing.co.tz'
            ],
            [
                'username' => 'org3', 
                'email' => 'org3@example.com', 
                'name' => 'Data Insights Hub', 
                'location' => 'Dodoma',
                'description' => 'Data analytics and business intelligence consulting firm.',
                'website' => 'https://datainsights.co.tz'
            ],
        ];
        
        foreach ($organizations as $orgData) {
            $user = User::findOne(['username' => $orgData['username']]);
            if (!$user) {
                $user = new User();
                $user->username = $orgData['username'];
                $user->email = $orgData['email'];
                $user->setPassword('password123');
                $user->generateAuthKey();
                $user->role = 'organization';
                $user->status = User::STATUS_ACTIVE;
                $user->created_at = time();
                $user->updated_at = time();
                $user->save();
                
                $org = new Organization();
                $org->user_id = $user->id;
                $org->name = $orgData['name'];
                $org->location = $orgData['location'];
                $org->description = $orgData['description'];
                $org->website = $orgData['website'];
                $org->save();
                
                echo "  ✅ Created organization: {$orgData['name']} (ID: {$org->id})\n";
            } else {
                echo "  ⚠️  Organization already exists: {$orgData['name']}\n";
            }
        }
    }
    
    private function createPositions()
    {
        echo "\n💼 Creating positions...\n";
        
        // Get organization IDs
        $orgs = Organization::find()->all();
        if (empty($orgs)) {
            echo "  ❌ No organizations found. Please create organizations first.\n";
            return;
        }
        
        $positions = [
            [
                'title' => 'Software Development Intern',
                'description' => 'Join our development team and work on real-world projects using modern technologies like React, Node.js, and Python. You will gain hands-on experience in full-stack development, participate in code reviews, and contribute to our product roadmap.',
                'field_of_study' => 'Computer Science',
                'skills_required' => 'Python, JavaScript, React, Git',
                'duration' => '3 months',
                'location' => 'Dar es Salaam',
                'criteria' => 'Basic programming knowledge, willingness to learn, good problem-solving skills',
                'organization_id' => $orgs[0]->id
            ],
            [
                'title' => 'Marketing Assistant',
                'description' => 'Assist in developing and executing marketing campaigns, manage social media accounts, conduct market research, and support the marketing team in various projects. Great opportunity to learn digital marketing strategies.',
                'field_of_study' => 'Marketing',
                'skills_required' => 'Communication, Social Media, Content Creation',
                'duration' => '4 months',
                'location' => 'Arusha',
                'criteria' => 'Good communication skills, creative thinking, social media savvy',
                'organization_id' => $orgs[1]->id
            ],
            [
                'title' => 'Data Analysis Trainee',
                'description' => 'Work with real datasets to extract meaningful insights, learn data visualization techniques, and support business decision-making processes. You will use tools like Excel, R, and Python for data analysis.',
                'field_of_study' => 'Statistics',
                'skills_required' => 'Excel, R/Python, Statistical Analysis',
                'duration' => '6 months',
                'location' => 'Dodoma',
                'criteria' => 'Basic statistics knowledge, analytical thinking, attention to detail',
                'organization_id' => $orgs[2]->id
            ],
            [
                'title' => 'UI/UX Design Intern',
                'description' => 'Design user interfaces and user experiences for our web and mobile applications. Work with design tools like Figma, Adobe XD, and learn about user research and usability testing.',
                'field_of_study' => 'Computer Science',
                'skills_required' => 'Figma, Adobe XD, User Research',
                'duration' => '3 months',
                'location' => 'Dar es Salaam',
                'criteria' => 'Creative thinking, attention to detail, basic design knowledge',
                'organization_id' => $orgs[0]->id
            ],
            [
                'title' => 'Business Development Intern',
                'description' => 'Support business development activities including market research, client outreach, proposal writing, and partnership development. Great opportunity to learn about business strategy and client relations.',
                'field_of_study' => 'Business Administration',
                'skills_required' => 'Communication, Research, Presentation',
                'duration' => '4 months',
                'location' => 'Arusha',
                'criteria' => 'Strong communication skills, analytical thinking, business acumen',
                'organization_id' => $orgs[1]->id
            ],
        ];
        
        foreach ($positions as $posData) {
            $position = new Position();
            $position->organization_id = $posData['organization_id'];
            $position->title = $posData['title'];
            $position->description = $posData['description'];
            $position->field_of_study = $posData['field_of_study'];
            $position->skills_required = $posData['skills_required'];
            $position->duration = $posData['duration'];
            $position->location = $posData['location'];
            $position->criteria = $posData['criteria'];
            $position->status = 'Active';
            $position->created_at = time();
            
            if ($position->save()) {
                echo "  ✅ Created position: {$posData['title']} at {$posData['location']}\n";
            } else {
                echo "  ❌ Failed to create position: {$posData['title']}\n";
                print_r($position->errors);
            }
        }
    }
    
    private function createApplications()
    {
        echo "\n📝 Creating sample applications...\n";
        
        $students = Student::find()->with('user')->all();
        $positions = Position::find()->all();
        
        if (empty($students) || empty($positions)) {
            echo "  ❌ No students or positions found. Please create them first.\n";
            return;
        }
        
        $applications = [
            ['student' => 0, 'position' => 0, 'status' => Application::STATUS_PENDING],
            ['student' => 1, 'position' => 1, 'status' => Application::STATUS_UNDER_REVIEW],
            ['student' => 2, 'position' => 2, 'status' => Application::STATUS_APPROVED],
            ['student' => 0, 'position' => 3, 'status' => Application::STATUS_PENDING],
            ['student' => 1, 'position' => 4, 'status' => Application::STATUS_REJECTED],
        ];
        
        foreach ($applications as $appData) {
            if (isset($students[$appData['student']]) && isset($positions[$appData['position']])) {
                $student = $students[$appData['student']];
                $position = $positions[$appData['position']];
                
                // Check if application already exists
                $existing = Application::findOne([
                    'user_id' => $student->user_id,
                    'position_id' => $position->id
                ]);
                
                if (!$existing) {
                    $application = new Application();
                    $application->user_id = $student->user_id;
                    $application->student_id = $student->id;
                    $application->position_id = $position->id;
                    $application->status = $appData['status'];
                    $application->cover_letter = "I am very interested in this position and believe my skills and experience make me a good fit for this role.";
                    $application->created_at = time();
                    $application->updated_at = time();
                    
                    if ($application->save()) {
                        echo "  ✅ Created application: {$student->user->username} -> {$position->title} ({$appData['status']})\n";
                    } else {
                        echo "  ❌ Failed to create application\n";
                        print_r($application->errors);
                    }
                } else {
                    echo "  ⚠️  Application already exists: {$student->user->username} -> {$position->title}\n";
                }
            }
        }
    }
    
    private function createNotifications()
    {
        echo "\n🔔 Creating sample notifications...\n";
        
        $students = Student::find()->with('user')->all();
        $organizations = Organization::find()->all();
        
        if (empty($students) || empty($organizations)) {
            echo "  ❌ No students or organizations found.\n";
            return;
        }
        
        $notifications = [
            [
                'user_id' => $students[0]->user_id,
                'title' => 'Application Status Update',
                'message' => 'Your application for Software Development Intern has been reviewed and is now under consideration.',
                'sender_type' => Notification::SENDER_TYPE_ORGANIZATION,
                'sender_id' => $organizations[0]->id,
                'action_url' => '/application/my-applications',
                'action_text' => 'View Applications'
            ],
            [
                'user_id' => $students[1]->user_id,
                'title' => 'Congratulations!',
                'message' => 'Your application for Marketing Assistant has been approved. Welcome to the team!',
                'sender_type' => Notification::SENDER_TYPE_ORGANIZATION,
                'sender_id' => $organizations[1]->id,
                'action_url' => '/application/my-applications',
                'action_text' => 'View Applications'
            ],
            [
                'user_id' => $students[2]->user_id,
                'title' => 'New Position Available',
                'message' => 'A new Data Analysis Trainee position has been posted that matches your field of study.',
                'sender_type' => Notification::SENDER_TYPE_SYSTEM,
                'sender_id' => 0,
                'action_url' => '/position/index',
                'action_text' => 'View Positions'
            ],
        ];
        
        foreach ($notifications as $notifData) {
            $notification = new Notification();
            $notification->user_id = $notifData['user_id'];
            $notification->title = $notifData['title'];
            $notification->message = $notifData['message'];
            $notification->sender_type = $notifData['sender_type'];
            $notification->sender_id = $notifData['sender_id'];
            $notification->action_url = $notifData['action_url'];
            $notification->action_text = $notifData['action_text'];
            $notification->is_read = 0;
            $notification->created_at = time();
            $notification->updated_at = time();
            
            if ($notification->save()) {
                echo "  ✅ Created notification: {$notifData['title']}\n";
            } else {
                echo "  ❌ Failed to create notification\n";
                print_r($notification->errors);
            }
        }
    }
    
    private function clearData()
    {
        echo "🗑️  Clearing existing data...\n";
        
        Application::deleteAll();
        Position::deleteAll();
        Organization::deleteAll();
        Student::deleteAll();
        User::deleteAll(['role' => ['student', 'organization']]);
        Notification::deleteAll();
        
        echo "  ✅ Data cleared\n";
    }
}
