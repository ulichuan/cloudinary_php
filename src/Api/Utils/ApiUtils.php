<?php
/**
 * This file is part of the Cloudinary PHP package.
 *
 * (c) Cloudinary
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cloudinary\Api;

use Cloudinary\ArrayUtils;
use Cloudinary\Asset\AssetTransformation;
use Cloudinary\ClassUtils;
use Cloudinary\Configuration\AccountConfig;
use Cloudinary\Utils;

/**
 * Class ApiUtils
 */
class ApiUtils
{
    const HEADERS_OUTER_DELIMITER         = '\n';
    const HEADERS_INNER_DELIMITER         = ':';
    const API_PARAM_DELIMITER             = ',';
    const CONTEXT_OUTER_DELIMITER         = '|';
    const CONTEXT_INNER_DELIMITER         = '=';
    const ARRAY_OF_ARRAYS_DELIMITER       = '|';
    const ASSET_TRANSFORMATIONS_DELIMITER = '|';
    const QUERY_STRING_INNER_DELIMITER    = '=';
    const QUERY_STRING_OUTER_DELIMITER    = '&';

    /**
     * Serializes a simple parameter, which can be a serializable object.
     *
     * In case the parameter is an array, it is joined using ApiUtils::API_PARAM_DELIMITER.
     *
     * @param string|array|mixed $parameter The parameter to serialize.
     *
     * @return string The resulting serialized parameter.
     *
     * @internal
     *
     * @see ApiUtils::API_PARAM_DELIMITER
     */
    public static function serializeSimpleApiParam($parameter)
    {
        return self::serializeParameter($parameter, self::API_PARAM_DELIMITER);
    }

    /**
     * Serializes 'headers' upload API parameter.
     *
     * @param $headers
     *
     * @return string The resulting serialized parameter.
     *
     * @internal
     */
    public static function serializeHeaders($headers)
    {
        return self::serializeParameter($headers, self::HEADERS_OUTER_DELIMITER, self::HEADERS_INNER_DELIMITER);
    }

    /**
     * Serializes 'context' and 'metadata' upload API parameters.
     *
     * @param array $context
     *
     * @return string The resulting serialized parameter.
     */
    public static function serializeContext($context)
    {
        return self::serializeParameter($context, self::CONTEXT_OUTER_DELIMITER, self::CONTEXT_INNER_DELIMITER);
    }

    /**
     * Serializes (encodes) json object to string.
     *
     * @param mixed $jsonParam Json object
     *
     * @return string The resulting serialized parameter.
     *
     * @internal
     */
    public static function serializeJson($jsonParam)
    {
        if ($jsonParam === null) {
            return null;
        }

        return json_encode($jsonParam); //TODO: serialize dates
    }

    /**
     * Commonly used util for building Cloudinary API params.
     *
     * @param      $parameter
     * @param      $outerDelimiter
     * @param null $innerDelimiter
     *
     * @return string The resulting serialized parameter.
     *
     * @internal
     *
     */
    public static function serializeParameter($parameter, $outerDelimiter = null, $innerDelimiter = null)
    {
        if (! is_array($parameter)) {
            return $parameter === null ? null : (string)$parameter; // can be a serializable object
        }

        return ArrayUtils::safeImplodeAssoc($parameter, $outerDelimiter, $innerDelimiter);
    }

    /**
     * Serializes array of nested array parameters.
     *
     * @param array $arrayOfArrays The input array.
     *
     * @return string The resulting serialized parameter.
     *
     * @internal
     */
    public static function serializeArrayOfArrays($arrayOfArrays)
    {
        $array = ArrayUtils::build($arrayOfArrays);

        if (! count($array)) {
            return null;
        }

        if (! is_array($array[0])) {
            return self::serializeSimpleApiParam($array);
        }

        $array = array_map('self::serializeSimpleApiParam', $array);


        return self::serializeParameter(self::ARRAY_OF_ARRAYS_DELIMITER, $array);
    }

    /**
     * Serializes asset transformations.
     *
     * @param array|string $transformations
     *
     * @return string The resulting serialized parameter.
     *
     * @internal
     */
    public static function serializeAssetTransformations($transformations)
    {
        $serializedTransformations = [];

        foreach (ArrayUtils::build($transformations) as $singleTransformation) {
            $serializedTransformations[] = (string)ClassUtils::verifyInstance(
                $singleTransformation,
                AssetTransformation::class
            );
        }

        return ArrayUtils::implodeFiltered(self::ASSET_TRANSFORMATIONS_DELIMITER, $serializedTransformations);
    }

    /**
     * Finalizes Upload API parameters.
     *
     * Normalizes parameters values, removes empty, adds timestamp.
     *
     * @param array $params Parameters to finalize.
     *
     * @return array Resulting parameters.
     *
     * @internal
     *
     */
    public static function finalizeUploadApiParams($params)
    {
        $additionalParams = [
            'timestamp' => Utils::unixTimeNow(),
        ];

        $params = array_merge($params, $additionalParams);

        ArrayUtils::convertBoolToIntStrings($params);

        return ArrayUtils::safeFilter($params);
    }

    /**
     * Serializes responsive breakpoints.
     *
     * @param array $breakpoints
     *
     * @return false|string|null
     *
     * @internal
     */
    protected static function serializeResponsiveBreakpoints($breakpoints)
    {
        if (! $breakpoints) {
            return null;
        }
        $breakpointsParams = [];
        foreach (ArrayUtils::build($breakpoints) as $breakpointSettings) {
            if ($breakpointSettings) {
                $transformation = ArrayUtils::get($breakpointSettings, 'transformation');
                if ($transformation) {
                    $breakpointSettings['transformation'] = $transformation;
                }
                $breakpointsParams[] = $breakpointSettings;
            }
        }

        return json_encode($breakpointsParams);
    }

    /**
     * @param array $parameters
     *
     * @return string
     *
     * @internal
     */
    public static function serializeQueryParams($parameters = [])
    {
        return ArrayUtils::safeImplodeAssoc(
            $parameters,
            self::QUERY_STRING_OUTER_DELIMITER,
            self::QUERY_STRING_INNER_DELIMITER,
            true
        );
    }

    /**
     * Signs parameters of the request.
     *
     * @param string $secret     The API secret of the account.
     * @param array  $parameters Parameters to sign.
     *
     * @return string The signature.
     *
     * @internal
     */
    public static function signParameters($secret, $parameters = [])
    {
        ksort($parameters);

        $signatureContent = self::serializeQueryParams($parameters);

        return Utils::sign($signatureContent, $secret);
    }

    /**
     * Adds signature and api_key to $parameters.
     *
     * @param array|null    $parameters
     * @param AccountConfig $account
     *
     * @internal
     */
    public static function signRequest(&$parameters, $account)
    {
        $parameters['signature'] = self::signParameters($account->apiSecret, $parameters);
        $parameters['api_key']   = $account->apiKey;
    }
}
