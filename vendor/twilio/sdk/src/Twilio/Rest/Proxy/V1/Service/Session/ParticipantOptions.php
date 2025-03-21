<?php
/**
 * This code was generated by
 * ___ _ _ _ _ _    _ ____    ____ ____ _    ____ ____ _  _ ____ ____ ____ ___ __   __
 *  |  | | | | |    | |  | __ |  | |__| | __ | __ |___ |\ | |___ |__/ |__|  | |  | |__/
 *  |  |_|_| | |___ | |__|    |__| |  | |    |__] |___ | \| |___ |  \ |  |  | |__| |  \
 *
 * Twilio - Proxy
 * This is the public Twilio REST API.
 *
 * NOTE: This class is auto generated by OpenAPI Generator.
 * https://openapi-generator.tech
 * Do not edit the class manually.
 */

namespace Twilio\Rest\Proxy\V1\Service\Session;

use Twilio\Options;
use Twilio\Values;

abstract class ParticipantOptions
{
    /**
     * @param string $friendlyName The string that you assigned to describe the participant. This value must be 255 characters or fewer. **This value should not have PII.**
     * @param string $proxyIdentifier The proxy phone number to use for the Participant. If not specified, Proxy will select a number from the pool.
     * @param string $proxyIdentifierSid The SID of the Proxy Identifier to assign to the Participant.
     * @return CreateParticipantOptions Options builder
     */
    public static function create(
        
        string $friendlyName = Values::NONE,
        string $proxyIdentifier = Values::NONE,
        string $proxyIdentifierSid = Values::NONE

    ): CreateParticipantOptions
    {
        return new CreateParticipantOptions(
            $friendlyName,
            $proxyIdentifier,
            $proxyIdentifierSid
        );
    }




}

class CreateParticipantOptions extends Options
    {
    /**
     * @param string $friendlyName The string that you assigned to describe the participant. This value must be 255 characters or fewer. **This value should not have PII.**
     * @param string $proxyIdentifier The proxy phone number to use for the Participant. If not specified, Proxy will select a number from the pool.
     * @param string $proxyIdentifierSid The SID of the Proxy Identifier to assign to the Participant.
     */
    public function __construct(
        
        string $friendlyName = Values::NONE,
        string $proxyIdentifier = Values::NONE,
        string $proxyIdentifierSid = Values::NONE

    ) {
        $this->options['friendlyName'] = $friendlyName;
        $this->options['proxyIdentifier'] = $proxyIdentifier;
        $this->options['proxyIdentifierSid'] = $proxyIdentifierSid;
    }

    /**
     * The string that you assigned to describe the participant. This value must be 255 characters or fewer. **This value should not have PII.**
     *
     * @param string $friendlyName The string that you assigned to describe the participant. This value must be 255 characters or fewer. **This value should not have PII.**
     * @return $this Fluent Builder
     */
    public function setFriendlyName(string $friendlyName): self
    {
        $this->options['friendlyName'] = $friendlyName;
        return $this;
    }

    /**
     * The proxy phone number to use for the Participant. If not specified, Proxy will select a number from the pool.
     *
     * @param string $proxyIdentifier The proxy phone number to use for the Participant. If not specified, Proxy will select a number from the pool.
     * @return $this Fluent Builder
     */
    public function setProxyIdentifier(string $proxyIdentifier): self
    {
        $this->options['proxyIdentifier'] = $proxyIdentifier;
        return $this;
    }

    /**
     * The SID of the Proxy Identifier to assign to the Participant.
     *
     * @param string $proxyIdentifierSid The SID of the Proxy Identifier to assign to the Participant.
     * @return $this Fluent Builder
     */
    public function setProxyIdentifierSid(string $proxyIdentifierSid): self
    {
        $this->options['proxyIdentifierSid'] = $proxyIdentifierSid;
        return $this;
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString(): string
    {
        $options = \http_build_query(Values::of($this->options), '', ' ');
        return '[Twilio.Proxy.V1.CreateParticipantOptions ' . $options . ']';
    }
}




