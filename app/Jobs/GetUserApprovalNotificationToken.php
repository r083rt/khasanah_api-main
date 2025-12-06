<?php

namespace App\Jobs;

use App\Models\Management\UserNotification;
use App\Models\Management\UserNotificationToken;
use App\Models\Permission;
use App\Models\RoleHasPermission;
use App\Models\User;

class GetUserApprovalNotificationToken extends Job
{
    protected $source_id;
    protected $reference_table;
    protected $pr_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($source_id, $reference_table, $pr_id)
    {
        $this->source_id = $source_id;
        $this->reference_table = $reference_table;
        $this->pr_id = $pr_id;
        $this->queue = 'notification';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $permission = Permission::where('action', 'approval-konversi-forecast.lihat')->first();
        if ($permission) {
            $roles = RoleHasPermission::where('permission_id', $permission->id)->pluck('role_id');
            $users = User::select('id')->whereIn('role_id', $roles)->pluck('id');
            $tokens = UserNotificationToken::whereIn('user_id', $users)->get();

            $title = 'Konversi Forecast';
            $content = 'Setujui konversi forecast ' . $this->pr_id;

            foreach ($tokens as $value) {
                $userNotification = UserNotification::create([
                    'user_id' => $value->user_id,
                    'source_id' => $this->source_id,
                    'reference_table' => $this->reference_table,
                    'title' => $title,
                    'content' => $content,
                    'datas' => ['id' => $this->source_id],
                    'type' => 'approval-forecast-conversion'
                ]);

                $data = [
                    'title' => $title,
                    'content' => $content,
                    'token' => $value->token,
                    'datas' => UserNotification::find($userNotification->id)->toArray()
                ];

                dispatch(new FirebaseNotification($data));
            }
        }
    }
}
