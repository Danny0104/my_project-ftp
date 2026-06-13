<?php
use yii\helpers\Html;
use common\components\CacheHelper;

$this->title = 'Admin Dashboard';

// Get cached statistics
$cacheHelper = new CacheHelper();
$stats = $cacheHelper->cacheStats('dashboard', 1800); // Cache for 30 minutes
$recentApplications = $cacheHelper->cacheStats('recent_applications', 900); // Cache for 15 minutes
$recentUsers = $cacheHelper->cacheStats('recent_users', 900); // Cache for 15 minutes

// Extract stats
$totalUsers = $stats['total_users'];
$totalStudents = $stats['total_students'];
$totalOrganizations = $stats['total_organizations'];
$totalApplications = $stats['total_applications'];
$totalPositions = $stats['total_positions'];
$totalNotifications = $stats['total_notifications'];
?>
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Field Training Admin Dashboard</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-2 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= $totalUsers ?></h3>
                <p>Total Users</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="<?= \yii\helpers\Url::to(['/user/index']) ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-2 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3><?= $totalStudents ?></h3>
                <p>Students</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <a href="<?= \yii\helpers\Url::to(['/student/index']) ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-2 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3><?= $totalOrganizations ?></h3>
                <p>Organizations</p>
            </div>
            <div class="icon">
                <i class="fas fa-building"></i>
            </div>
            <a href="<?= \yii\helpers\Url::to(['/organization/index']) ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-2 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= $totalApplications ?></h3>
                <p>Applications</p>
            </div>
            <div class="icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <a href="<?= \yii\helpers\Url::to(['/application/index']) ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-2 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= $totalPositions ?></h3>
                <p>Positions</p>
            </div>
            <div class="icon">
                <i class="fas fa-briefcase"></i>
            </div>
            <a href="<?= \yii\helpers\Url::to(['/position/index']) ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-2 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?= $totalNotifications ?></h3>
                <p>Notifications</p>
            </div>
            <div class="icon">
                <i class="fas fa-bell"></i>
            </div>
            <a href="<?= \yii\helpers\Url::to(['/notification/index']) ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">Recent Applications</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentApplications as $app): ?>
                            <tr>
                                <td><?= Html::encode($app->student ? ($app->student->user->username ?? 'Unknown') : 'Unknown') ?></td>
                                <td><?= Html::encode($app->position->title ?? 'Unknown') ?></td>
                                <td><span class="badge badge-<?= $app->getStatusBadgeClass() ?>"><?= Html::encode(ucfirst($app->status)) ?></span></td>
                                <td><?= date('Y-m-d', $app->created_at) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentApplications)): ?>
                            <tr><td colspan="4">No recent applications.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">Recent Users</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Date Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $user): ?>
                            <tr>
                                <td><?= Html::encode($user->username) ?></td>
                                <td><span class="badge badge-info"><?= Html::encode(ucfirst($user->role)) ?></span></td>
                                <td><span class="badge badge-<?= $user->status == User::STATUS_ACTIVE ? 'success' : 'warning' ?>"><?= Html::encode($user->status == User::STATUS_ACTIVE ? 'Active' : 'Inactive') ?></span></td>
                                <td><?= date('Y-m-d', $user->created_at) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentUsers)): ?>
                            <tr><td colspan="4">No recent users.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
