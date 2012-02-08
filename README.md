# HybridAuth 2.0.11-dev - Genealogy Providers

## About HybridAuth

HybridAuth enables developers to easily build social applications and tools 
to engage websites visitors and customers on a social level by implementing 
social sign-in, social sharing, users profiles, friends list, activities 
stream, status updates and more. 

The main goal of HybridAuth is to act as an abstract API between your application
and various social apis and identities providers such as Facebook, Twitter, 
MySpace and Google.

https://github.com/hybridauth/hybridauth

## About this implementation
Until a pull is made to the primary branch, you will need to grab my fork of HybridAuth:
https://github.com/dovy/hybridauth/

This implementation is for 3 of the major Genealogy providers. In addition I've created a custom example to springboard you into using the library.


Simply follow the HybridAuth setup instructions. Please note the following caveats.

- Each provider needs their own key/password/etc.
- FamlySearch requires a key to identify your application
- FamilySearch also requires you to contact them directly and give a specific URL endpoint
- Geni requires a key & secret combo for authentication
- Geni also is endpoint specific, but they allow you to specify this URL via the platform at: http://geni.com/platform/apps
- MyHeritage requires an id and secret combo
- MyHeritage auth is domain specific. You must tell them the domain you intend to utilize with their serice.


# To try out a live demo
## Visit: https://ancestorsync.com/examples/gen_hub/
None of your information is stored on the server except for sessions. Try it with security.
