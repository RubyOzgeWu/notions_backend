<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class DatabaseController extends Controller
{
    private $notionToken;
    private $notionApiUrl = "https://api.notion.com/v1/databases/";

    public function __construct()
    {
        // Laravel 自動讀取 .env
        $this->notionToken = config('services.notion.token');

        if (empty($this->notionToken)) {
            abort(500, "環境變數 NOTION_API_TOKEN 未設定");
        }
    }

    /**
     * 創建新資料庫
     */
    public function storeDatabase(Request $request)
    {
        $parentPageId = config('services.notion.page_id');

        if (empty($parentPageId)) {
            return response()->json(["error" => "環境變數 NOTION_PAGE_ID 未設定"], 400);
        }

        $dbName = $request->input('name');
        if (empty($dbName)) {
            return response()->json(["error" => "資料庫名稱為必填"], 400);
        }

        $url = $this->notionApiUrl;
        $headers = [
            "Authorization" => "Bearer " . $this->notionToken,
            "Content-Type" => "application/json",
            "Notion-Version" => "2022-06-28"
        ];

        $postData = [
            "parent" => ["type" => "page_id", "page_id" => $parentPageId],
            "title" => [[
                "type" => "text",
                "text" => ["content" => $dbName]
            ]],
            "properties" => [
                "名稱" => ["title" => new \stdClass()],
                "描述" => ["rich_text" => new \stdClass()],
                "狀態" => [
                    "select" => [
                        "options" => [
                            ["name" => "待處理", "color" => "yellow"],
                            ["name" => "進行中", "color" => "blue"],
                            ["name" => "已完成", "color" => "green"]
                        ]
                    ]
                ],
                "建立日期" => ["date" => new \stdClass()],
                "數值欄位" => ["number" => ["format" => "number"]]
            ],
        ];

        $response = Http::withHeaders($headers)->post($url, $postData);

        return response()->json($response->json(), $response->status());
    }

    /**
     * 讀取資料庫
     */
    public function indexDatabase($databaseId)
    {
        if (empty($databaseId)) {
            return response()->json(["error" => "databaseID 未設定"], 400);
        }

        $url = $this->notionApiUrl . $databaseId;
        $headers = [
            "Authorization" => "Bearer " . $this->notionToken,
            "Notion-Version" => "2022-06-28",
            "Content-Type" => "application/json"
        ];

        $response = Http::withHeaders($headers)->get($url);

        return response()->json($response->json(), $response->status());
    }
}
