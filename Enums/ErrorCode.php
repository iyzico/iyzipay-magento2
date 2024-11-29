<?php

namespace Iyzico\Iyzipay\Enums;

enum ErrorCode: int
{
    case NOT_SUFFICIENT_FUNDS = 10051;
    case DO_NOT_HONOUR = 10005;
    case INVALID_TRANSACTION = 10012;
    case LOST_CARD = 10041;
    case STOLEN_CARD = 10043;
    case EXPIRED_CARD = 10054;
    case INVALID_CVC2 = 10084;
    case NOT_PERMITTED_TO_CARDHOLDER = 10057;
    case NOT_PERMITTED_TO_TERMINAL = 10058;
    case FRAUD_SUSPECT = 10034;
    case RESTRICTED_BY_LAW = 10093;
    case CARD_NOT_PERMITTED = 10201;
    case UNKNOWN = 10204;
    case INVALID_CVC2_LENGTH = 10206;
    case REFER_TO_CARD_ISSUER = 10207;
    case INVALID_MERCHANT_OR_SP = 10208;
    case BLOCKED_CARD = 10209;
    case INVALID_CAVV = 10210;
    case INVALID_ECI = 10211;
    case BIN_NOT_FOUND = 10213;
    case COMMUNICATION_OR_SYSTEM_ERROR = 10214;
    case INVALID_CARD_NUMBER = 10215;
    case NO_SUCH_ISSUER = 10216;
    case DEBIT_CARDS_REQUIRES_3DS = 10217;
    case REQUEST_TIMEOUT = 10219;
    case NOT_PERMITTED_TO_INSTALLMENT = 10222;
    case REQUIRES_DAY_END = 10223;
    case RESTRICTED_CARD = 10225;
    case EXCEEDS_ALLOWABLE_PIN_TRIES = 10226;
    case INVALID_PIN = 10227;
    case ISSUER_OR_SWITCH_INOPERATIVE = 10228;
    case INVALID_EXPIRE_YEAR_MONTH = 10229;
    case INVALID_AMOUNT = 10232;

    public function getErrorMessage(): string
    {
        return match ($this) {
            self::NOT_SUFFICIENT_FUNDS => __('NOT_SUFFICIENT_FUNDS'),
            self::DO_NOT_HONOUR => __('DO_NOT_HONOUR'),
            self::INVALID_TRANSACTION => __('INVALID_TRANSACTION'),
            self::LOST_CARD => __('LOST_CARD'),
            self::STOLEN_CARD => __('STOLEN_CARD'),
            self::EXPIRED_CARD => __('EXPIRED_CARD'),
            self::INVALID_CVC2 => __('INVALID_CVC2'),
            self::NOT_PERMITTED_TO_CARDHOLDER => __('NOT_PERMITTED_TO_CARDHOLDER'),
            self::NOT_PERMITTED_TO_TERMINAL => __('NOT_PERMITTED_TO_TERMINAL'),
            self::FRAUD_SUSPECT => __('FRAUD_SUSPECT'),
            self::RESTRICTED_BY_LAW => __('RESTRICTED_BY_LAW'),
            self::CARD_NOT_PERMITTED => __('CARD_NOT_PERMITTED'),
            self::UNKNOWN => __('UNKNOWN'),
            self::INVALID_CVC2_LENGTH => __('INVALID_CVC2_LENGTH'),
            self::REFER_TO_CARD_ISSUER => __('REFER_TO_CARD_ISSUER'),
            self::INVALID_MERCHANT_OR_SP => __('INVALID_MERCHANT_OR_SP'),
            self::BLOCKED_CARD => __('BLOCKED_CARD'),
            self::INVALID_CAVV => __('INVALID_CAVV'),
            self::INVALID_ECI => __('INVALID_ECI'),
            self::BIN_NOT_FOUND => __('BIN_NOT_FOUND'),
            self::COMMUNICATION_OR_SYSTEM_ERROR => __('COMMUNICATION_OR_SYSTEM_ERROR'),
            self::INVALID_CARD_NUMBER => __('INVALID_CARD_NUMBER'),
            self::NO_SUCH_ISSUER => __('NO_SUCH_ISSUER'),
            self::DEBIT_CARDS_REQUIRES_3DS => __('DEBIT_CARDS_REQUIRES_3DS'),
            self::REQUEST_TIMEOUT => __('REQUEST_TIMEOUT'),
            self::NOT_PERMITTED_TO_INSTALLMENT => __('NOT_PERMITTED_TO_INSTALLMENT'),
            self::REQUIRES_DAY_END => __('REQUIRES_DAY_END'),
            self::RESTRICTED_CARD => __('RESTRICTED_CARD'),
            self::EXCEEDS_ALLOWABLE_PIN_TRIES => __('EXCEEDS_ALLOWABLE_PIN_TRIES'),
            self::INVALID_PIN => __('INVALID_PIN'),
            self::ISSUER_OR_SWITCH_INOPERATIVE => __('ISSUER_OR_SWITCH_INOPERATIVE'),
            self::INVALID_EXPIRE_YEAR_MONTH => __('INVALID_EXPIRE_YEAR_MONTH'),
            self::INVALID_AMOUNT => __('INVALID_AMOUNT'),
        };
    }

    public function getErrorGroup(): string
    {
        return match ($this) {
            self::NOT_SUFFICIENT_FUNDS => 'NOT_SUFFICIENT_FUNDS',
            self::DO_NOT_HONOUR => 'DO_NOT_HONOUR',
            self::INVALID_TRANSACTION => 'INVALID_TRANSACTION',
            self::LOST_CARD => 'LOST_CARD',
            self::STOLEN_CARD => 'STOLEN_CARD',
            self::EXPIRED_CARD => 'EXPIRED_CARD',
            self::INVALID_CVC2 => 'INVALID_CVC2',
            self::NOT_PERMITTED_TO_CARDHOLDER => 'NOT_PERMITTED_TO_CARDHOLDER',
            self::NOT_PERMITTED_TO_TERMINAL => 'NOT_PERMITTED_TO_TERMINAL',
            self::FRAUD_SUSPECT => 'FRAUD_SUSPECT',
            self::RESTRICTED_BY_LAW => 'RESTRICTED_BY_LAW',
            self::CARD_NOT_PERMITTED => 'CARD_NOT_PERMITTED',
            self::UNKNOWN => 'UNKNOWN',
            self::INVALID_CVC2_LENGTH => 'INVALID_CVC2_LENGTH',
            self::REFER_TO_CARD_ISSUER => 'REFER_TO_CARD_ISSUER',
            self::INVALID_MERCHANT_OR_SP => 'INVALID_MERCHANT_OR_SP',
            self::BLOCKED_CARD => 'BLOCKED_CARD',
            self::INVALID_CAVV => 'INVALID_CAVV',
            self::INVALID_ECI => 'INVALID_ECI',
            self::BIN_NOT_FOUND => 'BIN_NOT_FOUND',
            self::COMMUNICATION_OR_SYSTEM_ERROR => 'COMMUNICATION_OR_SYSTEM_ERROR',
            self::INVALID_CARD_NUMBER => 'INVALID_CARD_NUMBER',
            self::NO_SUCH_ISSUER => 'NO_SUCH_ISSUER',
            self::DEBIT_CARDS_REQUIRES_3DS => 'DEBIT_CARDS_REQUIRES_3DS',
            self::REQUEST_TIMEOUT => 'REQUEST_TIMEOUT',
            self::NOT_PERMITTED_TO_INSTALLMENT => 'NOT_PERMITTED_TO_INSTALLMENT',
            self::REQUIRES_DAY_END => 'REQUIRES_DAY_END',
            self::RESTRICTED_CARD => 'RESTRICTED_CARD',
            self::EXCEEDS_ALLOWABLE_PIN_TRIES => 'EXCEEDS_ALLOWABLE_PIN_TRIES',
            self::INVALID_PIN => 'INVALID_PIN',
            self::ISSUER_OR_SWITCH_INOPERATIVE => 'ISSUER_OR_SWITCH_INOPERATIVE',
            self::INVALID_EXPIRE_YEAR_MONTH => 'INVALID_EXPIRE_YEAR_MONTH',
            self::INVALID_AMOUNT => 'INVALID_AMOUNT',
        };
    }
}
