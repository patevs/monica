<?php

namespace Tests\Unit\Services\User\NotificationChannels;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use App\Models\UserNotificationChannel;
use App\Jobs\SendVerificationEmailChannel;
use App\Exceptions\EmailAlreadyExistException;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\User\NotificationChannels\CreateUserNotificationChannel;

class CreateUserNotificationChannelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_the_channel(): void
    {
        $ross = $this->createUser();
        $this->executeService($ross, $ross->account, 'slack');
    }

    /** @test */
    public function it_creates_the_channel_with_email(): void
    {
        $ross = $this->createUser();
        $this->executeService($ross, $ross->account, 'email');
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateUserNotificationChannel)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $this->executeService($ross, $account, 'slack');
    }

    /** @test */
    public function it_fails_if_email_already_exists_in_the_account(): void
    {
        $this->expectException(EmailAlreadyExistException::class);

        $ross = $this->createAdministrator();
        UserNotificationChannel::factory()->create([
            'content' => 'admin@admin.com',
        ]);
        $this->executeService($ross, $ross->account, 'email');
    }

    private function executeService(User $author, Account $account, string $channelType): void
    {
        Queue::fake();
        Bus::fake();
        Mail::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'label' => 'label',
            'type' => $channelType,
            'content' => 'admin@admin.com',
            'verify_email' => true,
        ];

        $channel = (new CreateUserNotificationChannel)->execute($request);

        $this->assertDatabaseHas('user_notification_channels', [
            'id' => $channel->id,
            'user_id' => $author->id,
            'label' => 'label',
            'type' => $channelType,
            'content' => 'admin@admin.com',
        ]);

        $this->assertInstanceOf(
            UserNotificationChannel::class,
            $channel
        );

        if ($channelType == UserNotificationChannel::TYPE_EMAIL) {
            Bus::assertDispatched(SendVerificationEmailChannel::class);
        }
    }
}