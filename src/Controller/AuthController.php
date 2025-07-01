<?php

namespace App\Controller;

use App\Core\Config;
use App\Core\Controller;
use App\Exception\InvalidLoginException;
use App\Exception\SignUpException;
use App\Helper\Recaptcha;
use App\Model\Account;
use App\Model\Account\User;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AuthController extends Controller
{

    /**
     * Assert reCAPTCHA validation
     *
     * This method checks if the reCAPTCHA response is valid.
     * If not, it throws an InvalidArgumentException.
     *
     * @throws \InvalidArgumentException If reCAPTCHA validation fails
     */
    protected function assertRecaptcha(): void
    {
        try {
            $config = Config::get('recaptcha');

            if (!$config || !is_array($config)) {
                throw new \RuntimeException('reCAPTCHA configuration is missing or invalid');
            }

            if (!$config['enabled']) {
                return; // reCAPTCHA is not enabled, skip validation
            }

            if (empty($config['site_key']) || empty($config['secret_key'])) {
                throw new \RuntimeException('reCAPTCHA configuration is missing or invalid');
            }
            $recaptchaResponse = $this->getRequest()->post('g-recaptcha-response');

            if (empty($recaptchaResponse)) {
                throw new \InvalidArgumentException('Please complete the reCAPTCHA');
            }

            if (!Recaptcha::validateRecaptcha($recaptchaResponse)) {
                throw new \InvalidArgumentException('reCAPTCHA validation failed');
            }
        } catch (\Throwable $e) {
            //Can add logging here if needed
            throw new \InvalidArgumentException('reCAPTCHA validation failed');
        }
    }

    /**
     * Sign up a new user
     *
     * @return void
     */
    public function signup(): void
    {

        if ($this->getRequest()->isPost()) {

            $data = $this->getRequest()->post();
            
            try {
                $this->assertRecaptcha();

                $data = Account::fillBilling($data);

                $this->validateSignUpData($data);

                $account = $this->createAccount($data);

                $this->sendWelcomeEmail($account);

                $this->redirect('/auth/login');

            } catch (\Throwable $e) {
                $this->getRequest()->addError($e->getMessage());
                $this->redirectReferer();
                return;
            }

        }

        $this->getRequest()->setSession('uid', null);

        $this->render(
            'auth/signup',
            [
                'data' => $this->getRequest()->post()
            ]
        );
    }

    /**
     * Validate signup data
     *
     * @param array $data The signup data
     * @return static
     * @throws SignUpException If validation fails
     */
    private function validateSignUpData(array $data): static
    {
        $requiredFields = [
            'email',
            'password',
            'password_repeat',
            'name',
            'street',
            'city',
            'state',
            'zip',
            'acreage_size'
        ];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new SignUpException('Please fill in all fields');
            }
        }
        if ($data['acreage_size'] < 0) {
            throw new SignUpException('Acreage size must be a positive number');
        }
        if ($data['password'] !== $data['password_repeat']) {
            throw new SignUpException('Passwords do not match');
        }
        if ((new Account())->findByEmail($data['email'])->getId()) {
            throw new SignUpException('Email already exists');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new SignUpException('Invalid email address');
        }
        
        // if (!preg_match('/^[0-9]{5}$/', $data['zip'])) {
        //     throw new SignUpException('Invalid zip code');
        // }
        // if (!preg_match('/^[0-9]{10}$/', $data['phone'])) {
        //     throw new SignUpException('Invalid phone number');
        // }

        return $this;
    }

    /**
     * Create a new account
     *
     * @param array $data
     */
    private function createAccount(array $data): Account
    {
        unset($data['password_repeat']);
        $data['password'] = Account::hashPassword($data['password']);
        $data['status'] = Account::STATUS_ACTIVE;

        $account = (new Account())->create($data);
        $account->load($account->getId());

        if (!$account->getId()) {
            throw new SignUpException('Could not create account');
        }

        return $account;
    }

    /**
     * Login user
     *
     * @return void
     */
    public function login(): void
    {
        if (!$this->getRequest()->isPost()) {
            $this->getRequest()->clearSession();
            $this->render('auth/login');
            return;
        }

        try {      
            $this->assertRecaptcha();

            $loginData = $this->getRequest()->getPost();

            $this->validateLoginData($loginData);

            $account = (new Account())->findByEmail($loginData['email'] ?? '');

            if (!$account || !$account->getId() || !password_verify($loginData['password'] ?? '', $account->getPassword())) {
                throw new InvalidLoginException('Invalid Credentials');
            }

            $this->getRequest()->setSession('uid', $account->getId());

            if (User::isAdmin()) {
                $this->redirect('/service/index');
                return;
            }

            if (!User::getInstance()->hasParcels()) {
                $this->redirect('/parcel/add');
                return;
            } else {
                $this->redirect('/parcel/index');
                return;
            }

        } catch (\Throwable $e) {
            $this->getRequest()->addError($e->getMessage());
        }

        $this->redirectReferer();
    }

    

    /**
     * Validate login data
     *
     * @param array $data  The login data
     */
    private function validateLoginData(array $data): void
    {
        if (empty($data['email']) || empty($data['password'])) {
            throw new \InvalidArgumentException('Please enter your email and password');
        }
        
    }

    /**
     * Logout user
     *
     * @return void
     */
    public function logout(): void
    {
        session_destroy();
        $this->redirect('/auth/login');
    }

    /**
     * Render forgot password page
     *
     * @return void
     */
    public function forgot(): void
    {
        if (!$this->getRequest()->isPost()) {
            $this->render('auth/forgot');
            return;
        }
        try {

            $this->assertRecaptcha();

            $data = $this->getRequest()->post();
            $userEmail = $data['email'];

            if (!$this->validateForgotPasswordData($data)) {
                throw new \InvalidArgumentException('Invalid data provided');
            }

            $account = (new Account())->loadByEmail($userEmail);
            if (!$account->getId()) {
                throw new \InvalidArgumentException('No account found with that email address');
            }
            $resetToken = $account->createResetToken();
            $resetLink = rtrim(Config::get('domain'), '/') . '/auth/reset?token=' . $resetToken;

            ob_start();
            require __DIR__ . '/../../views/email/reset_password.phtml';
            $emailContent = ob_get_clean();

            // Send reset email

            // Use PHPMailer to send the reset email

            
            try {
                $mail = new PHPMailer(true);
                $config = Config::get('email');
                $mail->isSMTP();
                $mail->setFrom($config['from_email'], $config['from_name']);
                $mail->addReplyTo($config['reply_to'], $config['from_name']);
                $mail->addAddress($userEmail);
                $mail->isHTML(true);
                $mail->SMTPAuth     = true;
                $mail->SMTPSecure   = PHPMailer::ENCRYPTION_STARTTLS;
                //$mail->SMTPSecure   = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Host         = $config['smtp_host'];
                $mail->Port         = $config['smtp_port'];
                $mail->Username     = $config['smtp_username'];
                $mail->Password     = $config['smtp_password'];
                $mail->Subject      = 'Password Reset Request';
                $mail->Body         = $emailContent;

                $mail->send();
            } catch (Exception $e) {
                throw new \RuntimeException('Could not send reset email. Mailer Error: ' . $mail->ErrorInfo);
            }

            $this->getRequest()->addInfo('A password reset link has been sent to your email address.');
            $this->redirect('/auth/login');
            return;

        } catch (\Throwable $e) {
            $this->getRequest()->addError('An error occurred: ' . $e->getMessage());
            
        }

        $this->redirectReferer();
    }

    /**
     * Send a welcome email to the user after successful signup
     *
     * @param Account $account The account object of the newly created user
     * @return void
     */
    private function sendWelcomeEmail(Account $account): void
    {
        try {
            ob_start();
            require __DIR__ . '/../../views/email/welcome.phtml';
            $emailContent = ob_get_clean();

            $mail = new PHPMailer(true);
            $config = Config::get('email');
            $mail->isSMTP();
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($account->get('email'));
            $mail->addReplyTo($config['reply_to'], $config['from_name']);
            $mail->isHTML(true);
            $mail->SMTPAuth     = true;
            $mail->SMTPSecure   = PHPMailer::ENCRYPTION_STARTTLS;
            //$mail->SMTPSecure   = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Host         = $config['smtp_host'];
            $mail->Port         = $config['smtp_port'];
            $mail->Username     = $config['smtp_username'];
            $mail->Password     = $config['smtp_password'];
            $mail->Subject      = 'Welcome to Aghawk Portal - Your account is Ready!';
            $mail->Body         = $emailContent;

            $mail->send();
        } catch (Exception $e) {
            // Log the error or handle it as needed
        }
    }

    /**
     * Reset password using a token
     * This method handles both displaying the reset form and processing the reset request
     *
     * @return void
     */
    public function reset(): void
    {
        $token = $this->getRequest()->request('token');
        if (!$token) {
            $this->getRequest()->addError('Invalid reset token');
            $this->redirect('/auth/forgot');
            return;
        }

        if (!$this->getRequest()->isPost()) {
            $account = (new Account())->findByResetToken($token);
            if (!$account->getId()) {
                $this->getRequest()->addError('Invalid or expired reset token');
                $this->redirect('/auth/forgot');
                return;
            }
            $this->render('auth/reset', ['token' => $token, 'account' => $account]);
            return;
        }

        try {

            $this->assertRecaptcha();

            $account = (new Account())->findByResetToken($token);
            if (!$account) {
                throw new \InvalidArgumentException('Invalid or expired reset token');
            }

            if ($this->getRequest()->isPost()) {
                $data = $this->getRequest()->getPost();
                $newPassword = $data['pvl'] ?? '';
                $newPasswordRepeat = $data['pvlr'] ?? '';
                if (empty($newPassword) || empty($newPasswordRepeat)) {
                    throw new \InvalidArgumentException('Please fill in all fields');
                }
                if ($newPassword !== $newPasswordRepeat) {
                    throw new \InvalidArgumentException('Passwords do not match');
                }

                $account->resetPassword($token, $newPassword, $newPasswordRepeat);

                $this->getRequest()->addInfo('Your password has been reset successfully.');
                $this->redirect('/auth/login');
            }

        } catch (\Throwable $e) {
            $this->getRequest()->addError('An error occurred: ' . $e->getMessage());
            $this->redirectReferer();
        }
    }

    /**
     * Validate forgot password data
     *
     * @param array $data The forgot password data
     * @return bool True if valid, false otherwise
     */
    private function validateForgotPasswordData(array $data): bool
    {
        if (empty($data['email'])) {
            $this->getRequest()->addError('Please enter your email address');
            return false;
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->getRequest()->addError('Invalid email address');
            return false;
        }

        $account = (new Account())->findByEmail($data['email']);
        if (!$account || !$account->getId()) {
            $this->getRequest()->addError('No account found with that email address');
            return false;
        }

        return true;
    }

}
