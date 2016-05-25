<?php
/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Discount\Business\QueryString;

use Generated\Shared\Transfer\ClauseTransfer;
use Spryker\Zed\Discount\Business\Exception\QueryStringException;
use Spryker\Zed\Discount\Business\QueryString\Specification\CollectorSpecification\CollectorSpecificationInterface;
use Spryker\Zed\Discount\Business\QueryString\Specification\DecisionRuleSpecification\DecisionRuleSpecificationInterface;
use Spryker\Zed\Discount\Dependency\Facade\DiscountToAssertionInterface;
use Spryker\Zed\Discount\Business\QueryString\Specification\SpecificationProviderInterface;

class SpecificationBuilder
{
    const OPEN_PARENTHESIS = '(';
    const CLOSE_PARENTHESIS = ')';

    const TYPE_COLLECTOR = 'collector';
    const TYPE_DECISION_RULE = 'decision-rule';


    /**
     * @var Tokenizer
     */
    protected $tokenizer;

    /**
     * @var DiscountToAssertionInterface
     */
    protected $assertionFacade;

    /**
     * @var SpecificationProviderInterface
     */
    protected $specificationProvider;

    /**
     * @param Tokenizer $tokenizer
     * @param DiscountToAssertionInterface $assertionFacade
     * @param SpecificationProviderInterface $specificationProvider
     */
    public function __construct(
        Tokenizer $tokenizer,
        DiscountToAssertionInterface $assertionFacade,
        SpecificationProviderInterface $specificationProvider
    ) {
        $this->tokenizer = $tokenizer;
        $this->assertionFacade = $assertionFacade;
        $this->specificationProvider = $specificationProvider;
    }

    /**
     * @param string $queryString
     *
     * @return CollectorSpecificationInterface|DecisionRuleSpecificationInterface
     */
    public function buildFromQueryString($queryString)
    {
        $tokens = $this->tokenizer
            ->tokenizeQueryString($queryString);

        return $this->build($tokens);
    }

    /**
     * @param string[] $tokens
     * @param int $currentTokenIndex
     *
     * @throws QueryStringException
     *
     * @return CollectorSpecificationInterface|DecisionRuleSpecificationInterface
     */
    protected function build(array $tokens, &$currentTokenIndex = 0)
    {
        $parentLeftSpecification = null;
        $compositeSpecification = null;
        $lastConditional = null;

        static $parenthesisDepth = 0;

        $countTokens = count($tokens);

        while ($countTokens > $currentTokenIndex) {

            $token = $this->cleanToken($tokens[$currentTokenIndex]);

            switch ($token) {
                case self::OPEN_PARENTHESIS:
                    $parenthesisDepth++;
                    $currentTokenIndex++;
                    $childSpecification = $this->build($tokens, $currentTokenIndex);

                    if ($parentLeftSpecification === null) {
                        $parentLeftSpecification = $childSpecification;
                    } else {
                        $compositeSpecification = $this->createComposite(
                            $lastConditional,
                            $parentLeftSpecification,
                            $childSpecification,
                            $compositeSpecification
                        );
                    }
                    break;

                case self::CLOSE_PARENTHESIS:
                    $parenthesisDepth--;

                    if ($compositeSpecification == null && $parentLeftSpecification !== null) {
                        return $parentLeftSpecification;
                    }

                    return $compositeSpecification;
                    break;

                case LogicalComparators::COMPARATOR_AND:
                    $lastConditional = $token;
                    break;

                case LogicalComparators::COMPARATOR_OR:
                    $lastConditional = $token;
                    break;

                default:

                    $clauseTransfer = $this->buildClause($tokens, $currentTokenIndex);

                    if ($parentLeftSpecification === null) {
                        $parentLeftSpecification = $this->specificationProvider->getSpecificationContext($clauseTransfer);
                        break;
                    }

                    $parentRightSpecification = $this->specificationProvider->getSpecificationContext($clauseTransfer);

                    $compositeSpecification = $this->createComposite(
                        $lastConditional,
                        $parentLeftSpecification,
                        $parentRightSpecification,
                        $compositeSpecification
                    );
                    break;
            }

            $currentTokenIndex++;
        }

        if ($parenthesisDepth > 0) {
            throw new QueryStringException('Parenthesis not matching.');
        }

        if ($compositeSpecification == null && $parentLeftSpecification !== null) {
            return $parentLeftSpecification;
        }

        return $compositeSpecification;
    }

    /**
     * @param array $tokens
     * @param int $currentTokenIndex
     *
     * @throws QueryStringException
     *
     * @return ClauseTransfer
     */
    protected function buildClause($tokens, &$currentTokenIndex)
    {
        $value = '';
        $fieldName = '';
        $comparatorOperator = '';
        $compoundComparator = '';

        $countTokens = count($tokens);

        while ($countTokens > $currentTokenIndex) {

            $token = $this->cleanToken($tokens[$currentTokenIndex]);

            if (!$fieldName) {
                $fieldName = $token;
                $this->validateField($fieldName);
                $currentTokenIndex++;
                continue;
            }

            if (!$comparatorOperator) {

                if (in_array($token, $this->getCompoundComparatorParts())) {
                    $compoundComparator .= $token . ' ';
                    $currentTokenIndex++;
                    continue;
                }

                if ($compoundComparator) {
                    $comparatorOperator = $compoundComparator;
                } else {
                    $comparatorOperator = $token;
                    $currentTokenIndex++;
                    continue;
                }
            }

            if (!$value) {
                $value = $this->cleanValue($token);
            }

            return $this->createClauseTransfer($fieldName, $comparatorOperator, $value);
        }

        throw new QueryStringException('Could not parse query clause.');
    }

    /**
     * @param string $conditional
     * @param CollectorSpecificationInterface|DecisionRuleSpecificationInterface $parentLeftSpecification
     * @param CollectorSpecificationInterface|DecisionRuleSpecificationInterface $parentRightSpecification
     * @param CollectorSpecificationInterface|DecisionRuleSpecificationInterface $compositeSpecification
     *
     * @return CollectorSpecificationInterface|DecisionRuleSpecificationInterface
     */
    protected function createComposite(
        $conditional,
        $parentLeftSpecification,
        $parentRightSpecification,
        $compositeSpecification
    ) {
        if (!$conditional) {
            return $compositeSpecification;
        }

        if ($compositeSpecification !== null) {
            $parentLeftSpecification = $compositeSpecification;
        }

        if ($conditional === LogicalComparators::COMPARATOR_AND) {
            $compositeSpecification = $this->specificationProvider->createAnd($parentLeftSpecification, $parentRightSpecification);
        } else if ($conditional === LogicalComparators::COMPARATOR_OR) {
            $compositeSpecification = $this->specificationProvider->createOr($parentLeftSpecification, $parentRightSpecification);
        }

        return $compositeSpecification;
    }

    /**
     * @param string $token
     *
     * @return string
     */
    protected function cleanToken($token)
    {
        return strtolower($token);
    }

    /**
     * @param string $fieldName
     * @param string $comparatorOperator
     * @param string $value
     *
     * @return ClauseTransfer
     */
    protected function createClauseTransfer($fieldName, $comparatorOperator, $value)
    {
        $clauseTransfer = new ClauseTransfer();
        $this->setClauseField($fieldName, $clauseTransfer);
        $clauseTransfer->setOperator(trim($comparatorOperator));
        $clauseTransfer->setValue(trim($value));

        return $clauseTransfer;
    }

    /**
     * @param string $fieldName
     * @param ClauseTransfer $clauseTransfer
     */
    protected function setClauseField($fieldName, ClauseTransfer $clauseTransfer)
    {
        if (strpos($fieldName, '.') !== false) {
            list($fieldName, $attribute) = explode('.', $fieldName);
            $clauseTransfer->setAttribute($attribute);
        }

        $clauseTransfer->setField(trim($fieldName));
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function cleanValue($value)
    {
        return str_replace('"', '', $value);
    }

    /**
     * @return string[]
     */
    protected function getCompoundComparatorParts()
    {
        return [
            'is',
            'not',
            'in',
            'does',
            'contain',
        ];
    }

    /**
     * @param string $fieldName
     *
     * @throws QueryStringException
     */
    protected function validateField($fieldName)
    {
        $matches = preg_match('/^[a-z0-9\.]+$/i', $fieldName);

        if ($matches === 0) {
            throw new QueryStringException(
                sprintf(
                    'Invalid "%s" field name',
                    $fieldName
                )
            );
        }

    }

}
