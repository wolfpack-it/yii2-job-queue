<?php

namespace JCIT\jobqueue\models\activeRecord;

use Cron\CronExpression;
use JCIT\jobqueue\interfaces\JobFactoryInterface;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\validators\InlineValidator;
use yii\validators\RequiredValidator;
use yii\validators\StringValidator;

/**
 * Class RecurringJob
 * @package JCIT\jobqueue\models\activeRecord
 *
 * @property int $id [int(11)]
 * @property string $name [varchar(255)]
 * @property string $description
 * @property string $cron
 * @property array $jobData [json]
 * @property int|null $queuedAt [timestamp]
 * @property int|null $createdAt [timestamp]
 * @property int|null $updatedAt [timestamp]
 * @property int|null $timeToRun [int]
 *
 * @property-read bool $isDue
 */
class RecurringJob extends ActiveRecord
{
    /**
     * @return array
     */
    public function behaviors(): array
    {
        return [
            TimestampBehavior::class => [
                'class' => TimestampBehavior::class,
                'value' => new Expression('NOW()'),
            ]
        ];
    }

    /**
     * @return bool
     */
    public function getIsDue(): bool
    {
        return CronExpression::factory($this->cron)->isDue();
    }

    /**
     * @return array|array[]
     */
    public function rules(): array
    {
        return [
            [['name', 'cron', 'jobData'], RequiredValidator::class],
            [['description'], StringValidator::class],
            [['cron'], function($attribute, $params, InlineValidator $validator) {
                try {
                    CronExpression::factory($this->cron);
                } catch (\InvalidArgumentException $e) {
                    $this->addError($attribute, $e->getMessage());
                }
            }],

            [['jobData'], function($attribute, $params, InlineValidator $validator){
                try {
                    \Yii::createObject(JobFactoryInterface::class)->createFromArray($this->jobData);
                } catch (\Throwable $t) {
                    $this->addError($attribute, $t->getMessage());
                }
            }]
        ];
    }
}
