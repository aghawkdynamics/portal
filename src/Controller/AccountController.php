<?php
namespace App\Controller;

use App\Core\Controller;
use App\Model\Account;
use App\Model\Account\User;

/**
 * Handles account listing (admin) and profile editing for current user
 */
class AccountController extends Controller
{
    /** List of accounts (admin usage) */
    public function index(): void
    {
        $accountCollection = (new Account())->getCollection()
            ->setItemMode(\App\Core\Model\Collection::ITEM_MODE_OBJECT)
            ->sort('name', 'ASC');

        $filters = $this->getRequest()->request('filters', []);

        $accountCollection->applyPostFilters(
            $filters
        );
        
        $accountCollection->setPage((int)$this->getRequest('page', 1));

        $this->render('account/list', 
            [
                'accounts'=>$accountCollection,
                'filters' => $filters,
            ]
    );
    }

    /** Profile page for logged‑in user  */
    public function profile(): void
    {
        if (User::isAdmin()) {
            $id = $this->getRequest()->request('id') ?? User::getInstance()->getId();
        } else {
            $id = User::getInstance()->getId();
        }

        if (!$id) {
            $this->redirect('/?q=auth/login');
            return;
        }

        $model = (new Account())->load($id);

        if (!$model->getId()) {
            $this->redirect('/?q=auth/login');
            return;
        }

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->post();
            if (!empty($_POST['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            if ($data) $model->update($data);
            $this->getRequest()->addInfo('Profile updated');
        }

        

        $this->render('account/profile', ['user'=>$model]);
    }

    /**
     * List all accounts for admin users.
     * 
     * @return void
     */
    public function list(): void
    {

        if (!\App\Model\Account\User::isAdmin()) {
            $this->getRequest()->addError('Access denied');
            $this->redirect('/?q=account/profile');
            return;
        }

        $accounts = (new Account())->getCollection()
            ->setItemMode(\App\Core\Model\Collection::ITEM_MODE_OBJECT)
            ->sort('name', 'ASC');

        $this->render('account/list', ['accounts' => $accounts]);

    }


    
    public function subscription_success(): void
    {
        try {
            $id = $this->getRequest()->getRequest('id') ?? User::getInstance()->getId();
            $subscriptionId = $this->getRequest()->getRequest('subscription_id');

            if (!$id) {
                $this->redirect('/?q=auth/login');
                return;
            }

            $account = (new Account())->load($id);

            if (!$account->getId()) {
                $this->redirect('/?q=auth/login');
                return;
            }

            $account
                ->setSubscribed(true, $subscriptionId)
                ->save();

            $this->getRequest()->addInfo('Subscription activated successfully.');
            
        } catch (\Exception $e) {
            $this->getRequest()->addError('An error occurred while processing your request.');
            
        }

        $this->redirectReferer();

    }


}
