# Middleware Stack

This package lets you build a stack of middlewares and run requests through them to their coresponding responses. Stack is Last In First Out stack (LIFO), meaning that middlewares that are added later are considered to be "outter" middlewares, and they are executed first.  

Example:

```php
$stack = new MiddlewareStack();

$stack->addMiddleware(function (ServerRequestInterface $request, ResponseInterface $response, callable $next = null) {
   // Route request to the contraoller, execute action, and encode action result to response

   if ($next) {
       $response = $next($request, $response);
   }
   
   return $response;
});

$stack->addMiddleware(function (ServerRequestInterface $request, ResponseInterface $response, callable $next = null) {
   if (!user_is_authenticated($request)) {
       return $response->withStatus(403); // Break here if user is not authenticated
   }

   if ($next) {
       $response = $next($request, $response);
   }
   
   return $response;
});

$response = $stack->process(new ServerRequest(), new Response());
```

This example shows a simple authorization check prior to request being sent further down to routing, controller, and result encoding. 

## Extension points

1. `MiddlewareStack` is called in the middle of execution, as just another middle. Override `__invoke` method if you need to inject extra functionlity there (like routing, with per-route middleware stack for example),
1. `finalizeProcessing` is called prior to $response being returned by `process` method. Override if you need to do something with response prior to returning it.

## History

Most middleware implementations that we found in November 2016. assumed and did too much, being mini frameworks themselves - they anticipated routing, substacking etc. 

Slim framework has a nice middleware stack implementation, but it was not available as a stand-alomponent, something that we needed, so we decided to extract it.
