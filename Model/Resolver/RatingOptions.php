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
class RatingOptions implements ResolverInterface
{
    protected $_ratingFactory;

    public function __construct(\Magento\Review\Model\RatingFactory $ratingFactory,
                                \Magento\Store\Model\StoreManagerInterface $storeManager){
           
            $this->_ratingFactory = $ratingFactory;
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
        $result = [];
        $ratingsOption = $this->_ratingFactory->create()->getResourceCollection()->addEntityFilter(
            'product'
        )->setPositionOrder()->addRatingPerStoreName(
            $this->storeManager->getStore()->getId()
        )->setStoreFilter(
            $this->storeManager->getStore()->getId()
        )->setActiveFilter(
            true
        )->load()->addOptionToItems();
        if ($ratingsOption && $ratingsOption->getSize()) {
            foreach ($ratingsOption as $key => $rating) {
                $result[$key]['rating_code'] =   $rating->getRatingCode();
                $options = $rating->getOptions();
                foreach ($options as $keyopt => $option) {
                    $result[$key]['options'][$keyopt]['rating_id'] =  $rating->getId();
                    $result[$key]['options'][$keyopt]['value'] =  $option->getId();
                }
            }
        }    

        return $result;
    }
}