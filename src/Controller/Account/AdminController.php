<?php
namespace App\Controller\Account;

use App\Core\Config;
use App\Core\Controller;
use App\Exception\SignUpException;
use App\Model\Account;
use App\Model\Account\User;
use RuntimeException;

class AdminController extends Controller
{
    /**
     * AccountController constructor.
     * Ensures that only admin users can access this controller.
     */
    public function __construct()
    {
        parent::__construct();
        if (!User::isAdmin()) {
            $this->redirect('error/404');
        }
    }
    /**
     * Get JSON list of all accounts for admin users.
     *
     * @return void
     */
    public function act(): void
    {
        $id = (int)$this->getRequest('id', 0);
        try {
            $account = (new Account())
                ->load($id);
            
            if (!$account->getId()) {
                throw new \Exception('Account not found');
            }

            $this->getRequest()->setSession('uid', $account->getId());
            $this->getRequest()->setSession('acting_as_user', $account->getId());

            $this->redirect('/');

        } catch (\Exception $e) {
            $this->getRequest()->addError($e->getMessage());
            $this->redirectReferer();
            return;
        }
    }

    /**
     * Create a new account (sign up).
     *
     * @return void
     */
    public function create() : void
    {
        
        if ($this->getRequest()->isPost()) {
            //use AuthController to handle the signup process. but it cant be extended
            $authController = new \App\Controller\AuthController();

            $data = $this->getRequest()->post();

            try {

                $data = Account::fillBilling($data);
                $account = $this->createAccount($data);

                $this->redirect('/account/profile', ['id' => $account->getId()]);
                exit;

            } catch (SignUpException $e) {
                $this->getRequest()->addError($e->getMessage());
            } catch (\Throwable $e) {
                $this->getRequest()->addError('An error occurred: ' . $e->getMessage());
            }
        }

        $this->render('auth/signup', [
            'data' => $this->getRequest()->post(),
            'implicit' => true, // to indicate that this is an admin signup
        ]);
    }


    /**
     * Validate signup data
     *
     * @param array $data The signup data
     * 
     * @return Account
     */
    private function createAccount(array $data): Account
    {
        unset($data['password_repeat']);
        $data['password'] = Account::hashPassword($data['password']);
        $data['status'] = Account::STATUS_ACTIVE;
        
        $account = (new Account());
        $account->create($data);

        if (!$account->getId()) {
            throw new \RuntimeException('Account creation failed');
        }

        return $account;
    }

    /**
     * Unsubscribe an account from notifications.
     *
     * @return void
     */
    public function subscribe(): void
    {
        $id = (int)$this->getRequest('id', 0);
        if (!$id) {
            $this->getRequest()->addError('Account ID is required');
            $this->redirectReferer();
            return;
        }

        $account = (new Account())->load($id);
        if (!$account->getId()) {
            $this->getRequest()->addError('Account not found');
            $this->redirectReferer();
            return;
        }

        $account->set('subscribed', 1);
        $account->save();

        $this->getRequest()->addInfo('Account subscribed successfully');
        $this->redirectReferer();
    }

    /**
     * Unsubscribe an account.
     *
     * @return void
     */
    public function unsubscribe(): void
    {
        $id = (int)$this->getRequest('id', 0);
        if (!$id) {
            $this->getRequest()->addError('Account ID is required');
            $this->redirectReferer();
            return;
        }

        $account = (new Account())->load($id);
        if (!$account->getId()) {
            $this->getRequest()->addError('Account not found');
            $this->redirectReferer();
            return;
        }

        $account->set('subscribed', 0);
        $account->save();

        $this->getRequest()->addInfo('Account unsubscribed successfully');
        $this->redirectReferer();
    }

}