<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class DbPageController extends Controller
{
    private $notionToken;
    private $notionDatabasesUrl = "https://api.notion.com/v1/databases/";
    private $notionPagesUrl = "https://api.notion.com/v1/pages";
    private $notionBlocksUrl = "https://api.notion.com/v1/blocks";

    public function __construct()
    {
        // Laravel 會自動讀取 .env
        $this->notionToken = config('services.notion.token');

        if (empty($this->notionToken)) {
            abort(500, "環境變數 NOTION_API_TOKEN 未設定");
        }
    }

    /**
     * 取得所有文件
     */
    public function indexPages($databaseId)
    {
        if (empty($databaseId)) {
            return response()->json(["error" => "未提供資料庫 ID"], 400);
        }

        // 確保 URL 正確
        $url = $this->notionDatabasesUrl . $databaseId . "/query";
        $headers = $this->getHeaders();

        // 確保 headers 有 API Key
        if (!$headers['Authorization']) {
            return response()->json(["error" => "Notion API Key 未設定"], 400);
        }

        // 排序條件
        $postData = [
            "sorts" => [
                [
                    "property" => "Due Date",
                    "direction" => "ascending"
                ]
            ]
        ];

        // 發送 API 請求
        $response = Http::withHeaders($headers)
            ->withBody(json_encode($postData), 'application/json')
            ->post($url);

        return response()->json($response->json(), $response->status());
    }

    /**
     * 取得文件 MetaData
     */
    public function showPageMeta($pageId)
    {
        if (empty($pageId)) {
            return response()->json(["error" => "未提供頁面 ID"], 400);
        }

        $url = $this->notionPagesUrl . '/' . $pageId;
        $headers = $this->getHeaders();

        $response = Http::withHeaders($headers)->get($url);

        return response()->json($response->json(), $response->status());
    }

    /**
     * 取得文件內容
     */
    public function showPage($pageId)
    {
        if (empty($pageId)) {
            return response()->json(["error" => "未提供頁面 ID"], 400);
        }

        $contentUrl = $this->notionBlocksUrl . '/' . $pageId . "/children";
        $headers = $this->getHeaders();

        $contentResponse = Http::withHeaders($headers)->get($contentUrl);
        $contentData = $contentResponse->json();

        $metaResponse = $this->showPageMeta($pageId);
        $metaData = $metaResponse->getData(true);

        $result = [
            "meta" => $metaData,
            "content" => $contentData
        ];

        return response()->json($result);
    }

    /**
     * 新增文件
     */
    public function storePage(Request $request, $databaseId)
    {
        if (empty($databaseId)) {
            return response()->json(["error" => "未提供資料庫 ID"], 400);
        }

        $title = $request->input('title');
        $dueDate = $request->input('due_date');
        $status = $request->input('status');

        $postData = [
            "parent" => ["database_id" => $databaseId],
            "properties" => [
                "Name" => [
                    "title" => [
                        [
                            "type" => "text",
                            "text" => ["content" => $title]
                        ]
                    ]
                ]
            ]
        ];

        if ($dueDate) {
            $postData["properties"]["Due Date"] = [
                "date" => ["start" => $dueDate]
            ];
        }

        if ($status) {
            $postData["properties"]["Status"] = [
                "status" => ["name" => $status]
            ];
        }

        $url = $this->notionPagesUrl;
        $headers = $this->getHeaders();

        // $response = Http::withHeaders($headers)->post($url, $postData);
        $response = Http::withHeaders($headers)
            ->withBody(json_encode($postData), 'application/json')
            ->post($url);

        return response()->json($response->json(), $response->status());
    }

    /**
     * 刪除資料庫文件
     */
    public function destroyPage($pageId)
    {
        if (empty($pageId)) {
            return response()->json(["error" => "未提供頁面 ID"], 400);
        }

        $url = $this->notionPagesUrl . '/' . $pageId;
        $headers = $this->getHeaders();
        $body = ["archived" => true];

        $response = Http::withHeaders($headers)->patch($url, $body);

        return response()->json($response->json(), $response->status());
    }

    /* 更新資料庫文件 */
    public function updatePage(Request $request, $databaseId, $pageId)
    {
        if (empty($databaseId) || empty($pageId)) {
            return response()->json(["error" => "未提供資料庫 ID 或頁面 ID"], 400);
        }

        $title = $request->input('title');
        $dueDate = $request->input('due_date');
        $status = $request->input('status');

        $updateData = ["properties" => []];

        if ($title) {
            $updateData["properties"]["Name"] = [
                "title" => [
                    [
                        "type" => "text",
                        "text" => ["content" => $title]
                    ]
                ]
            ];
        }

        if ($dueDate) {
            $updateData["properties"]["Due Date"] = [
                "date" => ["start" => $dueDate]
            ];
        }

        if ($status) {
            $updateData["properties"]["Status"] = [
                "status" => ["name" => $status]
            ];
        }

        $url = $this->notionPagesUrl . '/' . $pageId;
        $headers = $this->getHeaders();

        $response = Http::withHeaders($headers)
            ->withBody(json_encode($updateData), 'application/json')
            ->patch($url);

        return response()->json($response->json(), $response->status());
    }

    /**
     * 設定 Notion API Header
     */
    private function getHeaders()
    {
        return [
            "Authorization" => "Bearer " . $this->notionToken,
            "Notion-Version" => "2022-06-28",
            "Content-Type" => "application/json"
        ];
    }
}
