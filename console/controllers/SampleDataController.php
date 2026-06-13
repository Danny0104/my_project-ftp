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

class SampleDataController extends Controller
{
    public function actionIndex()
    {
        echo "Creating sample data...\n";
        
        // Create sample admin if not exists
        $admin = Admin::findOne(['username' => 'admin']);
        if (!$admin) {
            $admin = new Admin();
            $admin->username = 'admin';
            $admin->email = 'admin@example.com';
            $admin->setPassword('admin123');
            $admin->generateAuthKey();
            $admin->status = Admin::STATUS_ACTIVE;
            $admin->save();
            echo "Created admin user: admin/admin123\n";
        } else {
            echo "Admin user already exists: admin/admin123\n";
        }
        
        // Create sample students
        $students = [
            ['username' => 'student1', 'email' => 'student1@example.com', 'university' => 'University of Dar es Salaam', 'field_of_study' => 'Computer Science'],
            ['username' => 'student2', 'email' => 'student2@example.com', 'university' => 'Ardhi University', 'field_of_study' => 'Marketing'],
            ['username' => 'student3', 'email' => 'student3@example.com', 'university' => 'University of Dodoma', 'field_of_study' => 'Statistics'],
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
                $student->student_id = '2020-' . rand(1000, 9999);
                $student->university = $studentData['university'];
                $student->personal_statement = 'I am studying ' . $studentData['field_of_study'] . ' and looking for practical training opportunities.';
                $student->save();
                
                echo "Created student: {$studentData['username']}/password123\n";
            } else {
                echo "Student already exists: {$studentData['username']}/password123\n";
            }
        }
        
        // Create sample organizations
        $organizations = [
            ['username' => 'org1', 'email' => 'org1@example.com', 'name' => 'Acme Corporation Ltd', 'location' => 'Dar es Salaam'],
            ['username' => 'org2', 'email' => 'org2@example.com', 'name' => 'Green Marketing Solutions', 'location' => 'Arusha'],
            ['username' => 'org3', 'email' => 'org3@example.com', 'name' => 'Data Analytics Hub', 'location' => 'Dodoma'],
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
                $org->description = 'Leading organization in their field.';
                $org->save();
                
                echo "Created organization: {$orgData['username']}/password123\n";
            } else {
                echo "Organization already exists: {$orgData['username']}/password123\n";
            }
        }
        
        // Create sample positions
        $positions = [
            ['title' => 'Software Development Intern', 'organization_id' => 1, 'location' => 'Dar es Salaam', 'criteria' => 'Computer Science, Python, JavaScript'],
            ['title' => 'Marketing Assistant', 'organization_id' => 2, 'location' => 'Arusha', 'criteria' => 'Marketing, Communication, Social Media'],
            ['title' => 'Data Analysis Trainee', 'organization_id' => 3, 'location' => 'Dodoma', 'criteria' => 'Statistics, Excel, R/Python'],
        ];
        
        foreach ($positions as $posData) {
            $position = new Position();
            $position->organization_id = $posData['organization_id'];
            $position->title = $posData['title'];
            $position->description = 'Comprehensive training opportunity in ' . $posData['title'];
            $position->criteria = $posData['criteria'];
            $position->location = $posData['location'];
            $position->status = 'active';
            $position->created_at = time();
            $position->save();
            
            echo "Created position: {$posData['title']}\n";
        }
        
        // Create sample applications
        $applications = [
            ['student_id' => 1, 'position_id' => 1, 'status' => 'pending'],
            ['student_id' => 2, 'position_id' => 2, 'status' => 'accepted'],
            ['student_id' => 3, 'position_id' => 3, 'status' => 'rejected'],
        ];
        
        foreach ($applications as $appData) {
            $application = new Application();
            $application->student_id = $appData['student_id'];
            $application->position_id = $appData['position_id'];
            $application->status = $appData['status'];
            $application->created_at = time();
            $application->save();
            
            echo "Created application: Student {$appData['student_id']} -> Position {$appData['position_id']} ({$appData['status']})\n";
        }
        
        // Create sample notifications
        $notifications = [
            [
                'user_id' => 2, // student1
                'title' => 'Application Accepted - Acme Corporation',
                'message' => 'Congratulations! Your application for the Software Development Intern position has been accepted.',
                'sender_type' => 'organization',
                'sender_id' => 1,
                'action_url' => '/applications/view/1',
                'action_text' => 'View Application'
            ],
            [
                'user_id' => 4, // student2
                'title' => 'System Maintenance Notice',
                'message' => 'The system will undergo maintenance on Saturday from 2:00 AM to 6:00 AM.',
                'sender_type' => 'admin',
                'sender_id' => 1,
                'action_url' => '/maintenance-details',
                'action_text' => 'View Details'
            ],
            [
                'user_id' => 6, // student3
                'title' => 'Interview Invitation - Data Analytics Hub',
                'message' => 'You have been invited for an interview for the Data Analysis Trainee position.',
                'sender_type' => 'organization',
                'sender_id' => 3,
                'action_url' => '/interview/confirm/1',
                'action_text' => 'Confirm Interview'
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
            $notification->save();
            
            echo "Created notification: {$notifData['title']}\n";
        }
        
        echo "\nSample data created successfully!\n";
        echo "\nLogin Credentials:\n";
        echo "Admin: admin/admin123\n";
        echo "Students: student1/password123, student2/password123, student3/password123\n";
        echo "Organizations: org1/password123, org2/password123, org3/password123\n";
    }

    public function actionCreateSamplePositions()
    {
        // Get existing organizations
        $organizations = Organization::find()->all();
        if (empty($organizations)) {
            echo "No organizations found. Please create organizations first.\n";
            return;
        }

        $positions = [
            // TechCorp Solutions Ltd positions
            [
                'title' => 'Software Development Intern',
                'description' => 'Join our dynamic development team and work on cutting-edge projects using modern technologies like React, Node.js, Python, and cloud platforms. You will participate in the full software development lifecycle, from planning to deployment, and gain hands-on experience with agile methodologies.',
                'field_of_study' => 'Computer Science, Software Engineering, Information Technology',
                'skills_required' => 'Python, JavaScript, React, Git, Problem Solving, Teamwork',
                'duration' => '3 months',
                'status' => 'Active',
                'location' => 'Dar es Salaam',
                'criteria' => 'Basic programming knowledge, willingness to learn, good problem-solving skills, ability to work in a team',
                'organization_id' => $organizations[0]->id ?? 1
            ],
            [
                'title' => 'UI/UX Design Intern',
                'description' => 'Design intuitive and engaging user interfaces for our web and mobile applications. Work with design tools like Figma, Adobe XD, and learn about user research, usability testing, and design thinking methodologies.',
                'field_of_study' => 'Computer Science, Graphic Design, Human-Computer Interaction',
                'skills_required' => 'Figma, Adobe XD, User Research, Prototyping, Creative Thinking',
                'duration' => '3 months',
                'status' => 'Active',
                'location' => 'Dar es Salaam',
                'criteria' => 'Creative thinking, attention to detail, basic design knowledge, portfolio preferred',
                'organization_id' => $organizations[0]->id ?? 1
            ],
            [
                'title' => 'Data Science Trainee',
                'description' => 'Work with real-world datasets to extract meaningful insights and build predictive models. Learn machine learning algorithms, data visualization, and statistical analysis using Python, R, and SQL.',
                'field_of_study' => 'Computer Science, Statistics, Mathematics, Data Science',
                'skills_required' => 'Python, R, SQL, Statistics, Machine Learning, Data Visualization',
                'duration' => '6 months',
                'status' => 'Active',
                'location' => 'Dar es Salaam',
                'criteria' => 'Strong mathematical background, analytical thinking, programming experience preferred',
                'organization_id' => $organizations[0]->id ?? 1
            ],

            // Green Marketing Solutions positions
            [
                'title' => 'Digital Marketing Assistant',
                'description' => 'Assist in developing and executing digital marketing campaigns across various platforms. Manage social media accounts, create content, conduct market research, and analyze campaign performance using analytics tools.',
                'field_of_study' => 'Marketing, Business Administration, Communications',
                'skills_required' => 'Social Media Marketing, Content Creation, Google Analytics, SEO, Communication',
                'duration' => '4 months',
                'status' => 'Active',
                'location' => 'Arusha',
                'criteria' => 'Good communication skills, creative thinking, social media savvy, basic marketing knowledge',
                'organization_id' => $organizations[1]->id ?? 2
            ],
            [
                'title' => 'Content Creation Intern',
                'description' => 'Create engaging content for various marketing channels including blogs, social media, and email campaigns. Learn about content strategy, copywriting, and brand storytelling while working with our creative team.',
                'field_of_study' => 'Marketing, Communications, Journalism, English',
                'skills_required' => 'Writing, Content Strategy, Social Media, Photography, Video Editing',
                'duration' => '4 months',
                'status' => 'Active',
                'location' => 'Arusha',
                'criteria' => 'Excellent writing skills, creativity, attention to detail, portfolio of work preferred',
                'organization_id' => $organizations[1]->id ?? 2
            ],
            [
                'title' => 'Market Research Analyst',
                'description' => 'Conduct market research to identify trends, analyze consumer behavior, and provide insights for business decisions. Learn survey design, data collection methods, and statistical analysis.',
                'field_of_study' => 'Marketing, Business Administration, Statistics, Economics',
                'skills_required' => 'Research Methods, Data Analysis, Excel, Survey Design, Report Writing',
                'duration' => '5 months',
                'status' => 'Active',
                'location' => 'Arusha',
                'criteria' => 'Analytical thinking, research skills, attention to detail, basic statistics knowledge',
                'organization_id' => $organizations[1]->id ?? 2
            ],

            // Data Analytics Hub positions
            [
                'title' => 'Business Intelligence Analyst',
                'description' => 'Work with business stakeholders to understand requirements and create dashboards and reports using BI tools like Power BI and Tableau. Learn data modeling, ETL processes, and business intelligence best practices.',
                'field_of_study' => 'Business Administration, Information Systems, Statistics',
                'skills_required' => 'Power BI, Tableau, SQL, Excel, Data Visualization, Business Analysis',
                'duration' => '6 months',
                'status' => 'Active',
                'location' => 'Dodoma',
                'criteria' => 'Strong analytical skills, business acumen, attention to detail, basic SQL knowledge',
                'organization_id' => $organizations[2]->id ?? 3
            ],
            [
                'title' => 'Database Administrator Trainee',
                'description' => 'Learn database design, optimization, and maintenance. Work with SQL Server, MySQL, and learn about database security, backup strategies, and performance tuning.',
                'field_of_study' => 'Computer Science, Information Technology, Database Management',
                'skills_required' => 'SQL, Database Design, Problem Solving, System Administration',
                'duration' => '6 months',
                'status' => 'Active',
                'location' => 'Dodoma',
                'criteria' => 'Basic database knowledge, logical thinking, attention to detail, willingness to learn',
                'organization_id' => $organizations[2]->id ?? 3
            ],
            [
                'title' => 'Financial Data Analyst',
                'description' => 'Analyze financial data to identify trends, create forecasts, and support decision-making. Work with financial models, risk assessment, and regulatory reporting requirements.',
                'field_of_study' => 'Finance, Economics, Accounting, Statistics',
                'skills_required' => 'Excel, Financial Modeling, Statistical Analysis, Risk Assessment',
                'duration' => '5 months',
                'status' => 'Active',
                'location' => 'Dodoma',
                'criteria' => 'Strong mathematical background, analytical thinking, attention to detail, basic finance knowledge',
                'organization_id' => $organizations[2]->id ?? 3
            ],

            // Additional diverse positions
            [
                'title' => 'Human Resources Assistant',
                'description' => 'Support HR operations including recruitment, employee onboarding, performance management, and HR policy implementation. Learn about HR best practices, employment law, and organizational development.',
                'field_of_study' => 'Human Resources, Business Administration, Psychology',
                'skills_required' => 'Communication, Organization, Microsoft Office, Interpersonal Skills',
                'duration' => '4 months',
                'status' => 'Active',
                'location' => 'Dar es Salaam',
                'criteria' => 'Good communication skills, organizational ability, confidentiality, basic HR knowledge',
                'organization_id' => $organizations[0]->id ?? 1
            ],
            [
                'title' => 'Project Management Intern',
                'description' => 'Assist project managers in planning, executing, and monitoring projects. Learn project management methodologies, tools like Microsoft Project, and stakeholder communication.',
                'field_of_study' => 'Project Management, Business Administration, Engineering',
                'skills_required' => 'Project Planning, Communication, Organization, Microsoft Project, Leadership',
                'duration' => '5 months',
                'status' => 'Active',
                'location' => 'Arusha',
                'criteria' => 'Leadership potential, organizational skills, communication ability, basic project knowledge',
                'organization_id' => $organizations[1]->id ?? 2
            ],
            [
                'title' => 'Quality Assurance Tester',
                'description' => 'Test software applications to ensure quality and functionality. Learn testing methodologies, bug reporting, test case design, and automated testing tools.',
                'field_of_study' => 'Computer Science, Information Technology, Quality Assurance',
                'skills_required' => 'Testing Methodologies, Bug Reporting, Attention to Detail, Selenium',
                'duration' => '4 months',
                'status' => 'Active',
                'location' => 'Dodoma',
                'criteria' => 'Attention to detail, logical thinking, basic programming knowledge, patience',
                'organization_id' => $organizations[2]->id ?? 3
            ],
            [
                'title' => 'Customer Success Intern',
                'description' => 'Work with customers to ensure satisfaction and success with our products. Learn customer relationship management, support processes, and customer success strategies.',
                'field_of_study' => 'Business Administration, Marketing, Communications',
                'skills_required' => 'Customer Service, Communication, Problem Solving, CRM Systems',
                'duration' => '3 months',
                'status' => 'Active',
                'location' => 'Dar es Salaam',
                'criteria' => 'Excellent communication skills, empathy, problem-solving ability, customer focus',
                'organization_id' => $organizations[0]->id ?? 1
            ],
            [
                'title' => 'Operations Research Analyst',
                'description' => 'Use mathematical and analytical methods to help organizations make better decisions. Learn optimization techniques, simulation modeling, and decision analysis.',
                'field_of_study' => 'Operations Research, Mathematics, Industrial Engineering, Statistics',
                'skills_required' => 'Mathematical Modeling, Optimization, Statistics, Problem Solving',
                'duration' => '6 months',
                'status' => 'Active',
                'location' => 'Dodoma',
                'criteria' => 'Strong mathematical background, analytical thinking, optimization knowledge preferred',
                'organization_id' => $organizations[2]->id ?? 3
            ]
        ];

        $created = 0;
        $failed = 0;

        foreach ($positions as $positionData) {
            $position = new \common\models\Position();
            $position->organization_id = $positionData['organization_id'];
            $position->title = $positionData['title'];
            $position->description = $positionData['description'];
            $position->field_of_study = $positionData['field_of_study'];
            $position->skills_required = $positionData['skills_required'];
            $position->duration = $positionData['duration'];
            $position->status = $positionData['status'];
            $position->location = $positionData['location'];
            $position->criteria = $positionData['criteria'];
            $position->created_at = time();
            
            if ($position->save()) {
                echo "✅ Created position: {$position->title} at {$position->location}\n";
                $created++;
            } else {
                echo "❌ Failed to create position: {$position->title}\n";
                print_r($position->errors);
                $failed++;
            }
        }
        
        echo "\n📊 Summary:\n";
        echo "✅ Successfully created: {$created} positions\n";
        echo "❌ Failed to create: {$failed} positions\n";
        echo "\n🎉 Sample positions creation completed!\n";
    }

    public function actionCreateSampleApplications()
    {
        $applications = [
            [
                'student_id' => 1,
                'position_id' => 1,
                'status' => 'Pending Review',
                'feedback' => null,
            ],
            [
                'student_id' => 2,
                'position_id' => 2,
                'status' => 'Pending Review',
                'feedback' => null,
            ],
            [
                'student_id' => 3,
                'position_id' => 3,
                'status' => 'Accepted',
                'feedback' => 'Excellent candidate with strong analytical skills',
            ],
        ];

        foreach ($applications as $appData) {
            $application = new \common\models\Application();
            $application->student_id = $appData['student_id'];
            $application->position_id = $appData['position_id'];
            $application->status = $appData['status'];
            $application->feedback = $appData['feedback'];
            $application->created_at = time();
            
            if ($application->save()) {
                echo "Created application: Student {$application->student_id} for Position {$application->position_id}\n";
            } else {
                echo "Failed to create application\n";
                print_r($application->errors);
            }
        }
        
        echo "Sample applications created successfully!\n";
    }
} 