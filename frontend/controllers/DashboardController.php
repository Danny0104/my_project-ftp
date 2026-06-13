<?php
namespace frontend\controllers;

use yii\web\Controller;

class DashboardController extends Controller
{
    public function actionIndex()
    {
        if (\Yii::$app->user->isGuest) {
            return $this->redirect(['/site/login']);
        }

        $identity = \Yii::$app->user->identity;

        if ($identity && $identity->role === 'student') {
            return $this->redirect(['/dashboard/student']);
        }

        if ($identity && $identity->role === 'organization') {
            $this->layout = 'organization';
            return $this->render('organization');
        }

        // Admin / other roles: keep existing behavior (fallback).
        $this->layout = 'internal';
        return $this->render('index', [
            'model' => new \common\models\LoginForm(),
        ]);
    }

    public function actionStudent()
    {
        $this->layout = 'student';
        $this->view->params['ftpNavActive'] = 'dashboard';
        if (\Yii::$app->user->isGuest) {
            return $this->redirect(['/site/login']);
        }
        $userId = \Yii::$app->user->id;
        $student = \common\models\Student::findOrCreateForUserId($userId);
        if ($student === null) {
            return $this->redirect(['/dashboard']);
        }

        $statusRows = \common\models\Application::find()
            ->select(['status', 'cnt' => 'COUNT(*)'])
            ->where(['user_id' => $userId])
            ->groupBy('status')
            ->asArray()
            ->all();

        $applicationCount = 0;
        $acceptedCount = 0;
        $rejectedCount = 0;
        $pendingCount = 0;
        $underReviewCount = 0;
        $withdrawnCount = 0;

        foreach ($statusRows as $row) {
            $count = (int) ($row['cnt'] ?? 0);
            $applicationCount += $count;
            switch ($row['status']) {
                case \common\models\Application::STATUS_APPROVED:
                    $acceptedCount += $count;
                    break;
                case \common\models\Application::STATUS_REJECTED:
                    $rejectedCount += $count;
                    break;
                case \common\models\Application::STATUS_PENDING:
                    $pendingCount += $count;
                    break;
                case \common\models\Application::STATUS_UNDER_REVIEW:
                    $underReviewCount += $count;
                    break;
                case \common\models\Application::STATUS_WITHDRAWN:
                    $withdrawnCount += $count;
                    break;
            }
        }

        $recentApplications = \common\models\Application::find()
            ->where(['user_id' => $userId])
            ->with(['position', 'position.organization'])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(5)
            ->all();

        $applications = $recentApplications;

        // Notifications for this user
        $notifications = \common\models\Notification::find()
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(10)
            ->all();

        $publicPositionService = new \common\services\PublicPositionService();
        $recommendationService = new \common\services\OpportunityRecommendationService();

        $availablePositionsCount = $publicPositionService->countOpenPositions();

        // Get unread notifications count (exclude archived — matches sidebar badge)
        $unreadNotificationsCount = \common\models\Notification::getUnreadCount($userId);
        $unreadMessagesCount = (new \common\services\ChatService())->countUnreadForUser($userId);

        $profileCompletionService = new \common\services\ProfileCompletionService();
        $profileCompletion = $profileCompletionService->dashboardPercent($student);
        $profileTasks = array_values($profileCompletionService->dashboardTasks($student));

        // Get application trends (last 30 days)
        $thirtyDaysAgo = time() - (30 * 24 * 60 * 60);
        $recentApplicationsCount = \common\models\Application::find()
            ->where(['user_id' => $userId])
            ->andWhere(['>=', 'created_at', $thirtyDaysAgo])
            ->count();

        $studentField = $student->field_of_study ?? null;

        $appliedPositionIds = array_map(
            static fn(\common\models\Application $app): int => (int) $app->position_id,
            $recentApplications
        );
        $recommendedPositions = $recommendationService->forYou($student, $appliedPositionIds, 6);
        $featuredPosition = $recommendedPositions[0] ?? null;
        $upcomingDeadlines = array_map(
            static fn(array $item): \common\models\Position => $item['position'],
            $recommendationService->closingSoon(4)
        );

        $hour = (int) date('G');
        if ($hour < 12) {
            $greeting = 'Good Morning';
        } elseif ($hour < 17) {
            $greeting = 'Good Afternoon';
        } else {
            $greeting = 'Good Evening';
        }

        $displayName = $student->user->username ?? 'Student';
        $profilePercentile = min(92, max(8, (int) round($profileCompletion * 0.75 + 12)));
        $interviewCount = 0;
        if ($student) {
            $interviewCount = (int) \common\models\OrgInterview::find()
                ->where([
                    'student_id' => (int) $student->id,
                    'status' => \common\models\OrgInterview::STATUS_SCHEDULED,
                ])
                ->andWhere(['>=', 'scheduled_at', time()])
                ->count();
        }

        $announcements = array_slice($notifications, 0, 4);

        return $this->render('student-panel', [
            'student' => $student,
            'studentField' => $studentField,
            'applications' => $applications,
            'recentApplications' => $recentApplications,
            'notifications' => $notifications,
            'announcements' => $announcements,
            'applicationCount' => $applicationCount,
            'acceptedCount' => $acceptedCount,
            'rejectedCount' => $rejectedCount,
            'pendingCount' => $pendingCount,
            'underReviewCount' => $underReviewCount,
            'withdrawnCount' => $withdrawnCount,
            'availablePositionsCount' => $availablePositionsCount,
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'unreadMessagesCount' => $unreadMessagesCount,
            'profileCompletion' => $profileCompletion,
            'profilePercentile' => $profilePercentile,
            'profileTasks' => $profileTasks,
            'recentApplicationsCount' => $recentApplicationsCount,
            'recommendedPositions' => $recommendedPositions,
            'featuredPosition' => $featuredPosition,
            'upcomingDeadlines' => $upcomingDeadlines,
            'greeting' => $greeting,
            'displayName' => $displayName,
            'interviewCount' => $interviewCount,
        ]);
    }

    /**
     * Mark notification as read
     */
    public function actionMarkNotificationRead()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (!\Yii::$app->user->isGuest) {
            $notificationId = \Yii::$app->request->post('notification_id');
            $userId = \Yii::$app->user->id;
            
            $success = \common\models\Notification::markAsRead($notificationId, $userId);
            
            return [
                'success' => $success,
                'message' => $success ? 'Notification marked as read' : 'Failed to mark notification as read'
            ];
        }
        
        return ['success' => false, 'message' => 'User not authenticated'];
    }

    /**
     * Mark notification as unread
     */
    public function actionMarkNotificationUnread()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (!\Yii::$app->user->isGuest) {
            $notificationId = \Yii::$app->request->post('notification_id');
            $userId = \Yii::$app->user->id;
            
            $success = \common\models\Notification::markAsUnread($notificationId, $userId);
            
            return [
                'success' => $success,
                'message' => $success ? 'Notification marked as unread' : 'Failed to mark notification as unread'
            ];
        }
        
        return ['success' => false, 'message' => 'User not authenticated'];
    }

    /**
     * Delete notification
     */
    public function actionDeleteNotification()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (!\Yii::$app->user->isGuest) {
            $notificationId = \Yii::$app->request->post('notification_id');
            $userId = \Yii::$app->user->id;
            
            $success = \common\models\Notification::deleteNotification($notificationId, $userId);
            
            return [
                'success' => $success,
                'message' => $success ? 'Notification deleted' : 'Failed to delete notification'
            ];
        }
        
        return ['success' => false, 'message' => 'User not authenticated'];
    }

    /**
     * Mark all notifications as read
     */
    public function actionMarkAllNotificationsRead()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (!\Yii::$app->user->isGuest) {
            $userId = \Yii::$app->user->id;
            
            $count = \common\models\Notification::markAllAsRead($userId);
            
            return [
                'success' => true,
                'message' => "Marked {$count} notifications as read"
            ];
        }
        
        return ['success' => false, 'message' => 'User not authenticated'];
    }

    /**
     * Load more notifications
     */
    public function actionLoadMoreNotifications()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (!\Yii::$app->user->isGuest) {
            $userId = \Yii::$app->user->id;
            $offset = \Yii::$app->request->get('offset', 0);
            $limit = \Yii::$app->request->get('limit', 10);
            
            $notifications = \common\models\Notification::getNotificationsForUser($userId, $limit, $offset);
            
            $html = '';
            foreach ($notifications as $notification) {
                $html .= $this->renderPartial('_notification_item', ['notification' => $notification]);
            }
            
            $totalCount = \common\models\Notification::find()->where(['user_id' => $userId])->count();
            $hasMore = ($offset + $limit) < $totalCount;
            
            return [
                'success' => true,
                'notifications' => $html,
                'hasMore' => $hasMore
            ];
        }
        
        return ['success' => false, 'message' => 'User not authenticated'];
    }

    /**
     * Get notification statistics
     */
    public function actionGetNotificationStats()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (!\Yii::$app->user->isGuest) {
            $userId = \Yii::$app->user->id;
            $stats = \common\models\Notification::getNotificationStats($userId);
            
            return [
                'success' => true,
                'stats' => $stats
            ];
        }
        
        return ['success' => false, 'message' => 'User not authenticated'];
    }
} 