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
class ReviewCustomer implements ResolverInterface
{
    /**
     * Review resource model
     *
     * @var \Magento\Review\Model\ResourceModel\Review\CollectionFactory
     */
    protected $_reviewsColFactory;

    /**
     * product model
     *
     * @var \Magento\Catalog\Model\Product
     */
    protected $_product;
    protected $storeManager;
    protected $_reviewFactory;

    public function __construct(\Magento\Review\Model\ResourceModel\Review\CollectionFactory $reviewsColFactory,
                                \Magento\Catalog\Model\Product $product,
                                \Magento\Review\Model\ReviewFactory $reviewFactory,
                                \Magento\Store\Model\StoreManagerInterface $storeManager){
            $this->_reviewsColFactory = $reviewsColFactory;
            $this->_product = $product;
            $this->_reviewFactory = $reviewFactory;
            $this->storeManager = $storeManager;
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
        if (!isset($args['productId'])) {
            throw new GraphQlInputException(__('Required parameter "productId" is missing'));
        }
        $productId = $args['productId'];
        $reviewsCollection = $this->_reviewsColFactory->create()
        ->addStatusFilter(
            Review::STATUS_APPROVED
        )->addEntityFilter(
            'product',
            $productId
        )->setDateOrder();
        
        $product = $this->_product->load($productId);
        $storeId = $this->storeManager->getStore()->getId();
        $this->_reviewFactory->create()->getEntitySummary($product, $storeId);
        $ratingSummary = $product->getRatingSummary()->getRatingSummary();
        
        $reviewArray = [];
        $collection = $reviewsCollection->load()->addRateVotes();
        $count = count($collection);
        foreach ($collection as $reviewCollection) {
            $rating = $reviewCollection->getRatingVotes()->getData();
            $data = [
                "review_id"       => $reviewCollection->getReviewId(),
                "created_at"      => $reviewCollection->getCreatedAt(),
                "entity_id"       => $reviewCollection->getEntityId(),
                "entity_pk_value" => $reviewCollection->getEntityPkValue(),
                "status_id"       => $reviewCollection->getStatusId(),
                "detail_id"       => $reviewCollection->getDetailId(),
                "title"           => $reviewCollection->getTitle(),
                "detail"          => $reviewCollection->getDetail(),
                "nickname"        => $reviewCollection->getNickname(),
                "customer_id"     => $reviewCollection->getCustomerId(),
                "entity_code"     => $reviewCollection->getEntityCode(),
                "rating_votes"    => $rating
            ];
            $reviewArray = $data;
        }
        $reviewData = [
            "avg_rating_percent" => $ratingSummary,
            "count"              => $count,
            "reviews"            => $reviewArray
        ];
        
        return $reviewData;
    }
}