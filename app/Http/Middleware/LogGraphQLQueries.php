<?php declare(strict_types=1);

// namespace Nuwave\Lighthouse\Http\Middleware;
namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use GraphQL\Language\Parser;
use App\Jobs\InfluxQueue;

/**
 * Logs every incoming GraphQL query.
 */
class LogGraphQLQueries
{
    public const MESSAGE = 'Received GraphQL query.';

    public function __construct(
        protected LoggerInterface $logger,
    ) {}

    public function handle(Request $request, \Closure $next): mixed
    {
        $jsonParameters = $request->json()->all();
        // $this->logger->info(self::MESSAGE, $jsonParameters);

        // 记录 API的访问量！
        $json = $jsonParameters['query']; 
        $resultArr = Parser::parse($json)->toArray();
        $queryName = $resultArr['definitions'][0]['selectionSet']['selections'][0]['name']['value'];


        $argumentName = NULL;
        $argumentValue = NULL;
        if($resultArr['definitions'][0]['selectionSet']['selections'][0]['arguments']){
            $argumentName = $resultArr['definitions'][0]['selectionSet']['selections'][0]['arguments'][0]['name']['value'];
            $argumentValue = $resultArr['definitions'][0]['selectionSet']['selections'][0]['arguments'][0]['value']['value'];
        }
        $this->logger->info(self::MESSAGE, [$queryName, $argumentName, $argumentValue]);

        $tags['metric'] = 'GraphQL';
        $tags['queryName'] = $queryName;

        $fields = [];
        $fields['count'] = 1;
        $ip = $request->header('x-forwarded-for')??$request->ip();
        $fields['ip'] = $ip;
        $fields['argumentName'] = $argumentName;
        $fields['argumentValue'] = $argumentValue;

        $protocolLine = [
            'name' => 'click',
            'tags' => $tags,
            'fields' => $fields
        ];
        InfluxQueue::dispatchAfterResponse($protocolLine);

        return $next($request);
    }
}
