<?php
namespace app\models;

use app\rbac\models\Role;
use nenad\passwordStrength\StrengthValidator;
use yii\behaviors\TimestampBehavior;
use Yii;

/**
 * ------------------------------------------------------------------------------
 * This is the model class extending UserIdentity.
 * Here you can implement your custom user solutions.
 *
 * @property integer $id
 * @property string $username
 * @property string $email
 * @property string $password_hash
 * @property integer $status
 * @property string $auth_key
 * @property string $password_reset_token
 * @property string $account_activation_token
 * @property integer $created_at
 * @property integer $updated_at
 * 
 * @property Role $role
 * ------------------------------------------------------------------------------
 */
class User extends UserIdentity
{
    const STATUS_DELETED = 0;
    const STATUS_NOT_ACTIVE = 1;
    const STATUS_ACTIVE = 10;

    public $password;
    public $item_name;

    /**
     * =========================================================================
     * Returns the validation rules for attributes.
     * NOTE: We are using these rules when updating admin|The Creator account.
     * =========================================================================
     */
    public function rules()
    {
        return [
            [['username', 'email'], 'filter', 'filter' => 'trim'],
            [['username', 'email', 'status'], 'required'],
            ['email', 'email'],
            ['username', 'string', 'min' => 2, 'max' => 255],

            // password field is required on 'create' scenario
            ['password', 'required', 'on' => 'create'],
            // use passwordStrengthRule() method to determine password strength
            $this->passwordStrengthRule(),
                      
            ['username', 'unique', 'message' => 'This username has already been taken.'],
            ['email', 'unique', 'message' => 'This email address has already been taken.'],
        ];
    }

    /**
     * =========================================================================
     * Set password rule based on our setting value (Force Strong Password).
     * =========================================================================
     * 
     * @return array  Password strength rule
     * _________________________________________________________________________
     */
    private function passwordStrengthRule()
    {
        // get setting value for 'Force Strong Password'
        $fsp = Yii::$app->params['fsp'];

        // password strength rule is determined by StrengthValidator 
        // presets are located in: vendor/nenad/yii2-password-strength/presets.php
        $strong = [['password'], StrengthValidator::className(), 'preset'=>'normal'];

        // normal yii rule
        $normal = ['password', 'string', 'min' => 6];

        // if 'Force Strong Password' is set to 'true' use $strong rule, else use $normal rule
        return ($fsp) ? $strong : $normal;
    }

    /**
     * =========================================================================
     * Returns a list of behaviors that this component should behave as. 
     * =========================================================================
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * Relation with Role class. 
     */
    public function getRole()
    {
        // User has_one Role via Role.user_id -> id
        return $this->hasOne(Role::className(), ['user_id' => 'id']);
    }    

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'username' => Yii::t('app', 'Username'),
            'email' => Yii::t('app', 'Email'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'item_name' => Yii::t('app', 'Role'),
        ];
    }

//------------------------------------------------------------------------------------------------//
// USER FINDERS
//------------------------------------------------------------------------------------------------//

    /**
     * =========================================================================
     * Finds user by username.
     * =========================================================================
     *
     * @param  string  $username
     *
     * @return static|null 
     * _________________________________________________________________________
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username]);
    }  
    
    /**
     * =========================================================================
     * Finds user by email.
     * =========================================================================
     *
     * @param  string  $email
     *
     * @return static|null 
     * _________________________________________________________________________
     */
    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email]);
    } 

    /**
     * =========================================================================
     * Finds user by password reset token.
     * =========================================================================
     *
     * @param  string  $token  Password reset token.
     *
     * @return static|null 
     * _________________________________________________________________________
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) 
        {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    /**
     * =========================================================================
     * Finds user by account activation token.
     * =========================================================================
     *
     * @param  string  $token  Account activation token.
     *
     * @return static|null
     * _________________________________________________________________________
     */
    public static function findByAccountActivationToken($token)
    {
        return static::findOne([
            'account_activation_token' => $token,
            'status' => User::STATUS_NOT_ACTIVE,
        ]);
    }
  
//------------------------------------------------------------------------------------------------//
// HELPERS
//------------------------------------------------------------------------------------------------//

    /**
     * Generates new password reset token.
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }
    
    /**
     * Removes password reset token.
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    /**
     * Finds out if password reset token is valid.
     * 
     * @param  string  $token Password reset token.
     * 
     * @return boolean        
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) 
        {
            return false;
        }

        $expire = Yii::$app->params['user.passwordResetTokenExpire'];

        $parts = explode('_', $token);

        $timestamp = (int) end($parts);
        
        return $timestamp + $expire >= time();
    }

    /**
     * Generates new account activation token.
     */
    public function generateAccountActivationToken()
    {
        $this->account_activation_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes account activation token.
     */
    public function removeAccountActivationToken()
    {
        $this->account_activation_token = null;
    }

    /**
     * Returns the user status in nice format.
     * 
     * @param  null|integer $status Status integer value if sent to method.
     * 
     * @return string               Nicely formated status.
     */
    public function getStatusName($status = null)
    {
        $status = (empty($status)) ? $this->status : $status ;

        if ($status === self::STATUS_DELETED)
        {
            return "Deleted";
        } 
        elseif ($status === self::STATUS_NOT_ACTIVE)
        {
            return "Inactive";
        }
        else
        {
            return "Active";
        }        
    }  

    /**
     * Returs the array of possible user status values.
     * 
     * @return array
     */
    public function statusList()
    {
        $statusArray = [
            self::STATUS_ACTIVE     => 'Active',
            self::STATUS_NOT_ACTIVE => 'Inactive',
            self::STATUS_DELETED    => 'Deleted'
        ];

        return $statusArray;
    }

    /**
     * Returns the role name ( item_name )
     * 
     * @return string
     */
    public function getRoleName()
    {
        return $this->role->item_name;
    }
}
