<?php

namespace CMS\Infrastructure\Extension\Auth\TokenParsers;

use CMS\Infrastructure\Extension\Auth\Session;
use CMS\Infrastructure\Extension\Auth\Exception;
use CMS\Infrastructure\Extension\Auth\TokenParserInterface;
use CMS\Infrastructure\Constant\ErrorCodes;

class JWTTokenParser implements TokenParserInterface
{
    const ALGORITHM_HS256 = 'HS256';
    const ALGORITHM_HS512 = 'HS512';
    const ALGORITHM_HS384 = 'HS384';
    const ALGORITHM_RS256 = 'RS256';

    protected $algorithm;
    protected $secret;


    public function __construct($secret, $algorithm = self::ALGORITHM_HS256)
    {
        if (!class_exists('\Firebase\JWT\JWT')) {
            throw new Exception(ErrorCodes::GENERAL_SYSTEM, 'JWT class is needed for the JWT token parser');
        }

        $this->algorithm = $algorithm;
        $this->secret = $secret;
    }

    public function setAlgorithm($algorithm)
    {
        $this->algorithm = $algorithm;
    }

    public function setSecret($secret)
    {
        $this->secret = $secret;
    }


    public function getToken(Session $session, $expirationTime = null)
    {
        $tokenData = $this->create($session->getUser()->Id, $session->getStartTime(), $session->getExpirationTime());

        return $this->encode($tokenData);
    }

    protected function create($user, $iat, $exp)
    {
        return [
            /*
            The sub (subject) claim identifies the principal
            that is the subject of the JWT. The Claims in a
            JWT are normally statements about the subject.
            The subject value MUST either be scoped to be
            locally unique in the context of the issuer or
            be globally unique. The processing of this claim
            is generally application specific. The sub value
            is a case-sensitive string containing a
            StringOrURI value. Use of this claim is OPTIONAL.
            ------------------------------------------------*/
            "sub" => $user,

            /*
            The iat (issued at) claim identifies the time at
            which the JWT was issued. This claim can be used
            to determine the age of the JWT. Its value MUST
            be a number containing a NumericDate value.
            Use of this claim is OPTIONAL.
            ------------------------------------------------*/
            "iat" => $iat,

            /*
            The exp (expiration time) claim identifies the
            expiration time on or after which the JWT MUST NOT
            be accepted for processing. The processing of the
            exp claim requires that the current date/time MUST
            be before the expiration date/time listed in the
            exp claim. Implementers MAY provide for some small
            leeway, usually no more than a few minutes,
            to account for clock skew. Its value MUST be a
            number containing a NumericDate value.
            Use of this claim is OPTIONAL.
            ------------------------------------------------*/
            "exp" => $exp,
        ];
    }

    public function encode($token)
    {
        return \Firebase\JWT\JWT::encode($token, $this->secret, $this->algorithm);
    }

    public function getSession($token)
    {
        $tokenData = $this->decode($token);
        return new Session([
            User => $tokenData->sub,
            StartTime => $tokenData->iat,
            ExpirationTime => $tokenData->exp,
            Token => $token
        ]);
    }

    public function decode($token)
    {
        return \Firebase\JWT\JWT::decode($token, $this->secret, [$this->algorithm]);
    }
}
