<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "notification".
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $message
 * @property string $sender_type admin|organization|system
 * @property int $sender_id
 * @property string $action_url
 * @property string $action_text
 * @property int $is_read
 * @property int $created_at
 * @property string $notification_type
 * @property string $category
 * @property string $priority
 * @property int $is_archived
 * @property int|null $related_id
 * @property int|null $conversation_id
 *
 * @property User $user
 * @property Organization $organization
 * @property Admin $admin
 */
class Notification extends ActiveRecord
{
    const SENDER_TYPE_ADMIN = 'admin';
    const SENDER_TYPE_ORGANIZATION = 'organization';
    const SENDER_TYPE_SYSTEM = 'system';

    const TYPE_SYSTEM = 'system';
    const TYPE_APPLICATION = 'application';
    const TYPE_INTERVIEW = 'interview';
    const TYPE_NEW_MESSAGE = 'new_message';
    const TYPE_PROFILE = 'profile';
    const TYPE_DEADLINE = 'deadline';
    const TYPE_OPPORTUNITY = 'opportunity';
    const TYPE_SECURITY = 'security';
    const TYPE_ANNOUNCEMENT = 'announcement';

    const CATEGORY_APPLICATIONS = 'applications';
    const CATEGORY_INTERVIEWS = 'interviews';
    const CATEGORY_MESSAGES = 'messages';
    const CATEGORY_PROFILE = 'profile';
    const CATEGORY_OPPORTUNITIES = 'opportunities';
    const CATEGORY_SYSTEM = 'system';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_ANNOUNCEMENTS = 'announcements';

    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'notification';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($insert) {
            try {
                (new \common\services\EmailQueueService())->enqueueForNotification($this);
            } catch (\Throwable $e) {
                \Yii::warning('Notification email queue failed: ' . $e->getMessage(), __METHOD__);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'title', 'message', 'sender_type'], 'required'],
            [['user_id', 'sender_id', 'is_read', 'created_at', 'updated_at', 'is_archived', 'related_id', 'conversation_id'], 'integer'],
            [['message'], 'string'],
            [['title', 'action_url', 'action_text'], 'string', 'max' => 255],
            [['sender_type'], 'string', 'max' => 20],
            [['notification_type'], 'string', 'max' => 50],
            [['category'], 'string', 'max' => 30],
            [['priority'], 'string', 'max' => 10],
            [['sender_type'], 'in', 'range' => [self::SENDER_TYPE_ADMIN, self::SENDER_TYPE_ORGANIZATION, self::SENDER_TYPE_SYSTEM]],
            [['priority'], 'in', 'range' => [self::PRIORITY_NORMAL, self::PRIORITY_HIGH, self::PRIORITY_URGENT]],
            [['is_read', 'is_archived'], 'default', 'value' => 0],
            [['notification_type'], 'default', 'value' => self::TYPE_SYSTEM],
            [['category'], 'default', 'value' => self::CATEGORY_SYSTEM],
            [['priority'], 'default', 'value' => self::PRIORITY_NORMAL],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'title' => 'Title',
            'message' => 'Message',
            'sender_type' => 'Sender Type',
            'sender_id' => 'Sender ID',
            'action_url' => 'Action URL',
            'action_text' => 'Action Text',
            'is_read' => 'Is Read',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Gets query for [[Organization]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrganization()
    {
        return $this->hasOne(Organization::class, ['id' => 'sender_id']);
    }

    /**
     * Gets query for [[Admin]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAdmin()
    {
        return $this->hasOne(Admin::class, ['id' => 'sender_id']);
    }

    /**
     * Get sender name based on sender type
     *
     * @return string
     */
    public function getSenderName()
    {
        switch ($this->sender_type) {
            case self::SENDER_TYPE_ADMIN:
                return $this->admin ? $this->admin->name : 'System Administrator';
            case self::SENDER_TYPE_ORGANIZATION:
                return $this->organization ? $this->organization->name : 'Organization';
            case self::SENDER_TYPE_SYSTEM:
                return 'System';
            default:
                return 'Unknown';
        }
    }

    /**
     * Create a system alert notification (read-only update, not a chat message).
     *
     * @param array{sender_type?: string, sender_id?: int, action_url?: string, action_text?: string, related_id?: int, conversation_id?: int, priority?: string} $options
     */
    public static function createAlert(
        int $userId,
        string $type,
        string $category,
        string $title,
        string $message,
        array $options = []
    ): bool {
        $notification = new self();
        $notification->user_id = $userId;
        $notification->notification_type = $type;
        $notification->category = $category;
        $notification->title = $title;
        $notification->message = $message;
        $notification->sender_type = $options['sender_type'] ?? self::SENDER_TYPE_SYSTEM;
        $notification->sender_id = (int) ($options['sender_id'] ?? 0);
        $notification->action_url = $options['action_url'] ?? null;
        $notification->action_text = $options['action_text'] ?? null;
        $notification->related_id = isset($options['related_id']) ? (int) $options['related_id'] : null;
        $notification->conversation_id = isset($options['conversation_id']) ? (int) $options['conversation_id'] : null;
        $notification->priority = $options['priority'] ?? self::PRIORITY_NORMAL;

        return $notification->save();
    }

    /**
     * Create notification from admin
     *
     * @param int $userId
     * @param string $title
     * @param string $message
     * @param int $adminId
     * @param string|null $actionUrl
     * @param string|null $actionText
     * @return bool
     */
    public static function createFromAdmin($userId, $title, $message, $adminId, $actionUrl = null, $actionText = null)
    {
        $notification = new self();
        $notification->user_id = $userId;
        $notification->title = $title;
        $notification->message = $message;
        $notification->sender_type = self::SENDER_TYPE_ADMIN;
        $notification->sender_id = $adminId;
        $notification->action_url = $actionUrl;
        $notification->action_text = $actionText;
        $notification->notification_type = self::TYPE_ANNOUNCEMENT;
        $notification->category = self::CATEGORY_ANNOUNCEMENTS;
        $notification->priority = self::PRIORITY_HIGH;

        return $notification->save();
    }

    /**
     * Create notification from organization (system alert — not chat content).
     *
     * @param array{notification_type?: string, category?: string, priority?: string, related_id?: int, conversation_id?: int} $meta
     */
    public static function createFromOrganization($userId, $title, $message, $organizationId, $actionUrl = null, $actionText = null, array $meta = [])
    {
        $notification = new self();
        $notification->user_id = $userId;
        $notification->title = $title;
        $notification->message = $message;
        $notification->sender_type = self::SENDER_TYPE_ORGANIZATION;
        $notification->sender_id = $organizationId;
        $notification->action_url = $actionUrl;
        $notification->action_text = $actionText;
        $notification->notification_type = $meta['notification_type'] ?? self::TYPE_APPLICATION;
        $notification->category = $meta['category'] ?? self::CATEGORY_APPLICATIONS;
        $notification->priority = $meta['priority'] ?? self::PRIORITY_NORMAL;
        $notification->related_id = isset($meta['related_id']) ? (int) $meta['related_id'] : null;
        $notification->conversation_id = isset($meta['conversation_id']) ? (int) $meta['conversation_id'] : null;
        
        return $notification->save();
    }

    /**
     * Create system notification
     *
     * @param array{notification_type?: string, category?: string, priority?: string, related_id?: int, conversation_id?: int} $meta
     */
    public static function createSystemNotification($userId, $title, $message, $actionUrl = null, $actionText = null, array $meta = [])
    {
        $notification = new self();
        $notification->user_id = $userId;
        $notification->title = $title;
        $notification->message = $message;
        $notification->sender_type = self::SENDER_TYPE_SYSTEM;
        $notification->sender_id = 0;
        $notification->action_url = $actionUrl;
        $notification->action_text = $actionText;
        $notification->notification_type = $meta['notification_type'] ?? self::TYPE_SYSTEM;
        $notification->category = $meta['category'] ?? self::CATEGORY_SYSTEM;
        $notification->priority = $meta['priority'] ?? self::PRIORITY_NORMAL;
        $notification->related_id = isset($meta['related_id']) ? (int) $meta['related_id'] : null;
        $notification->conversation_id = isset($meta['conversation_id']) ? (int) $meta['conversation_id'] : null;
        
        return $notification->save();
    }

    /**
     * Mark notification as read
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public static function markAsRead($notificationId, $userId)
    {
        $notification = self::findOne(['id' => $notificationId, 'user_id' => $userId]);
        if ($notification) {
            $notification->is_read = 1;
            return $notification->save();
        }
        return false;
    }

    /**
     * Mark notification as unread
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public static function markAsUnread($notificationId, $userId)
    {
        $notification = self::findOne(['id' => $notificationId, 'user_id' => $userId]);
        if ($notification) {
            $notification->is_read = 0;
            return $notification->save();
        }
        return false;
    }

    /**
     * Mark all notifications as read for user
     *
     * @param int $userId
     * @return int
     */
    public static function markAllAsRead($userId)
    {
        return self::updateAll(['is_read' => 1], ['user_id' => $userId, 'is_read' => 0, 'is_archived' => 0]);
    }

    public static function archiveNotification(int $notificationId, int $userId): bool
    {
        $notification = self::findOne(['id' => $notificationId, 'user_id' => $userId]);
        if ($notification) {
            $notification->is_archived = 1;
            return $notification->save(false);
        }
        return false;
    }

    public static function archiveAllRead(int $userId): int
    {
        return self::updateAll(['is_archived' => 1], ['user_id' => $userId, 'is_read' => 1, 'is_archived' => 0]);
    }

    /**
     * Delete notification
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public static function deleteNotification($notificationId, $userId)
    {
        $notification = self::findOne(['id' => $notificationId, 'user_id' => $userId]);
        if ($notification) {
            return $notification->delete();
        }
        return false;
    }

    /**
     * Get notifications for user with pagination
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getNotificationsForUser($userId, $limit = 10, $offset = 0, bool $includeArchived = false)
    {
        $query = self::find()
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC]);
        if (!$includeArchived) {
            $query->andWhere(['is_archived' => 0]);
        }
        return $query->limit($limit)->offset($offset)->all();
    }

    public static function getUnreadCount(int $userId): int
    {
        return (int) self::find()
            ->where(['user_id' => $userId, 'is_read' => 0, 'is_archived' => 0])
            ->count();
    }

    /**
     * Get notification statistics for user
     *
     * @param int $userId
     * @return array
     */
    public static function getNotificationStats($userId)
    {
        $total = self::find()->where(['user_id' => $userId])->count();
        $unread = self::find()->where(['user_id' => $userId, 'is_read' => 0])->count();
        $fromAdmin = self::find()->where(['user_id' => $userId, 'sender_type' => self::SENDER_TYPE_ADMIN])->count();
        $fromOrganization = self::find()->where(['user_id' => $userId, 'sender_type' => self::SENDER_TYPE_ORGANIZATION])->count();

        return [
            'total' => $total,
            'unread' => $unread,
            'from_admin' => $fromAdmin,
            'from_organization' => $fromOrganization,
        ];
    }
} 