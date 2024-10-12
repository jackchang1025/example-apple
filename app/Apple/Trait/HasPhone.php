<?php

namespace App\Apple\Trait;

use App\Models\Phone;
use Illuminate\Support\Facades\DB;

trait HasPhone
{
    /**
     * 存储电话号码的变量，默认值为null。
     * 当有实际电话号码数据时，此变量将存储该数据。
     */
    protected ?Phone $phone = null;

    /**
     * 存储不包含的电话号码列表。
     *
     * @var array
     */
    protected array $notInPhones = [];

    /**
     * 获取未关联的电话号码列表。
     *
     * @return array 返回一个包含未关联电话号码的数组。
     */
    public function getNotInPhones(): array
    {
        return $this->notInPhones;
    }

    /**
     * 设置不在电话列表中的电话号码数组。
     *
     * @param array $notInPhones 一个包含不应分配给用户的电话号码的数组。
     *
     * @return void
     */
    public function setNotInPhones(array $notInPhones): void
    {
        $this->notInPhones = $notInPhones;
    }

    /**
     * 添加不在电话列表中的ID。
     *
     * @param int|string $id 不在电话列表中的ID，可以是整数或字符串类型。
     *
     * @return void
     */
    public function addNotInPhones(int|string $id): void
    {
        $this->notInPhones[] = $id;
    }

    /**
     * 获取电话号码。
     *
     * 如果当前模型中没有电话号码，该方法将尝试获取一个可用的电话号码并赋值给 `$this->phone`。
     *
     * @return Phone 返回电话号码对象
     * @throws \Throwable
     */
    public function getPhone(): Phone
    {
        return $this->phone ??= $this->getAvailablePhone();
    }

    /**
     * 设置电话号码。
     *
     * @param Phone|null $phone 电话号码对象，可以为空以移除电话号码设置。
     *
     * @return void
     */
    public function setPhone(?Phone $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * 刷新并获取新的电话号码。
     *
     * 该方法将尝试获取一个新的可用电话号码，并更新当前对象的电话号码属性。
     * 成功刷新后，将返回新的电话号码对象。如果获取过程中出现问题，可能返回 null。
     *
     * @return Phone|null 返回刷新后的电话号码对象，如果未成功获取则为 null。
     * @throws \Throwable
     */
    public function refreshPhone(): ?Phone
    {
        $this->phone = $this->getAvailablePhone();
        return $this->phone;
    }

    /**
     * 获取可用的电话号码。
     *
     * 该方法从数据库中查询状态为正常且电话地址与电话号码均不为空的记录，
     * 返回第一个找到的可用电话对象。若无符合条件的数据，则抛出异常。
     *
     * @return Phone 已经查询到的可用电话对象
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException 当没有找到符合条件的电话记录时
     */
    public function fetchAvailablePhone(): Phone
    {
        return Phone::query()
            ->where('status', Phone::STATUS_NORMAL)
            ->whereNotNull(['phone_address', 'phone'])
            ->firstOrFail();
    }

    /**
     * 获取一个可用的电话号码实体。
     *
     * 此方法在数据库事务中执行，以确保数据一致性。它会查询状态为正常、
     * 具有电话地址和电话号码、且不在指定排除列表中的电话记录，然后锁定所选记录并更新其状态为绑定，
     * 最后返回该电话实体。
     *
     * @return Phone 已被锁定并更新状态为绑定的电话实体
     * @throws \Throwable
     */
    protected function getAvailablePhone(): Phone
    {
        return DB::transaction(function () {
            $phone = Phone::query()
                ->where('status', Phone::STATUS_NORMAL)
                ->whereNotNull(['phone_address', 'phone'])
                ->whereNotIn('id', $this->getNotInPhones())
                ->lockForUpdate()
                ->firstOrFail();

            $phone->update(['status' => Phone::STATUS_BINDING]);

            return $phone;
        });
    }
}
