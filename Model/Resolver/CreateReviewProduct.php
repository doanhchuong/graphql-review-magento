<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Evolve\ReviewGraphql\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Review\Model\Review;

class CreateReviewProduct implements ResolverInterface
{
    protected $reviewFactory;
    protected $storeManager;
    /**
     * Rating model
     *
     * @var \Magento\Review\Model\RatingFactory
     */
    protected $ratingFactory;
    public function __construct(
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Review\Model\RatingFactory $ratingFactory
    ) {
        $this->reviewFactory = $reviewFactory;
        $this->storeManager = $storeManager;
        $this->ratingFactory = $ratingFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $currentUserId = $context->getUserId();
        if (!isset($args['input']['nickname'])) {
            throw new GraphQlInputException(__('Required parameter "nickname" is missing'));
        }
        if (!isset($args['input']['title'])) {
            throw new GraphQlInputException(__('Required parameter "title" is missing'));
        }
        if (!isset($args['input']['detail'])) {
            throw new GraphQlInputException(__('Required parameter "detail" is missing'));
        }
        if (!isset($args['input']['productId'])) {
            throw new GraphQlInputException(__('Required parameter "productId" is missing'));
        }

        $productId = $args['input']['productId'];
        $data = [
            'nickname' => $args['input']['nickname'],
            'title' =>  $args['input']['title'],
            'detail' => $args['input']['detail']
        ];
        $rating = [];
        if (isset($args['input']['ratings']) && $args['input']['ratings']) {
            $rating = json_decode(str_replace('\'','"',$args['input']['ratings']), true);
        }
        $review = $this->reviewFactory->create()->setData($data);
        $review->unsetData('review_id');
        $validate = $review->validate();
        if ($validate === true) {
            try {
                $review->setEntityId($review->getEntityIdByCode(Review::ENTITY_PRODUCT_CODE))
                    ->setEntityPkValue($productId)
                    ->setStatusId(Review::STATUS_PENDING)
                    ->setCustomerId($currentUserId)
                    ->setStoreId($this->storeManager->getStore()->getId())
                    ->setStores([$this->storeManager->getStore()->getId()])
                    ->save();
                if(count($rating)) {
                    foreach ($rating as $ratingId => $optionId) {
                        $this->ratingFactory->create()
                            ->setRatingId($ratingId)
                            ->setReviewId($review->getId())
                            ->setCustomerId($currentUserId)
                            ->addOptionVote($optionId, $productId);
                    }
                }
                $review->aggregate();
                return true;
            } catch (\Exception $e) {
               return false;
            }
        } else {
            return false;
        }
    }
}