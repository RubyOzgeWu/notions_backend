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
        $ticket = $request->input('ticket');
        $priority = $request->input('priority');
        $status = $request->input('status');
        $description = $request->input('description');
        $todoList = $request->input('todo_list');

        $children = [];

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

        // 日期
        if ($dueDate) {
            $postData["properties"]["Due Date"] = [
                "date" => ["start" => $dueDate]
            ];
        }

        // 優先級
        if ($priority) {
            $postData["properties"]["Priority"] = [
                "select" => ["name" => $priority]
            ];
        }

        // 狀態
        if ($status) {
            $postData["properties"]["Status"] = [
                "status" => ["name" => $status]
            ];
        }

        // 任務單號 
        if (!empty($ticket)) {
            $children[] = [
                "object" => "block",
                "type" => "toggle",
                "toggle" => [
                    "rich_text" => [
                        [
                            "type" => "text",
                            "text" => ["content" => "ticket"]
                        ]
                    ],
                    "children" => [
                        [
                            "object" => "block",
                            "type" => "paragraph",
                            "paragraph" => [
                                "rich_text" => [
                                    [
                                        "type" => "text",
                                        "text" => [
                                            "content" => $ticket
                                        ],
                                        "annotations" => [
                                            "bold" => false,
                                            "italic" => false,
                                            "strikethrough" => false,
                                            "underline" => false,
                                            "code" => false,
                                            "color" => "default"
                                        ]
                                    ]
                                ],
                                "color" => "default"
                            ]
                        ]
                    ]
                ]
            ];
        }

        // 描述
        if (!empty($description)) {
            $children[] = [
                "object" => "block",
                "type" => "toggle",
                "toggle" => [
                    "rich_text" => [
                        [
                            "type" => "text",
                            "text" => ["content" => "description"]
                        ]
                    ],
                    "children" => [
                        [
                            "object" => "block",
                            "type" => "paragraph",
                            "paragraph" => [
                                "rich_text" => [
                                    [
                                        "type" => "text",
                                        "text" => [
                                            "content" => $description
                                        ],
                                        "annotations" => [
                                            "bold" => false,
                                            "italic" => false,
                                            "strikethrough" => false,
                                            "underline" => false,
                                            "code" => false,
                                            "color" => "default"
                                        ]
                                    ]
                                ],
                                "color" => "default"
                            ]
                        ]
                    ]
                ]
            ];
        }

        // 待辦清單
        if (is_array($todoList)) {
            foreach ($todoList as $item) {
                $children[] = [
                    "object" => "block",
                    "type" => "to_do",
                    "to_do" => [
                        "rich_text" => [
                            [
                                "type" => "text",
                                "text" => [
                                    "content" => $item["text"] ?? ""
                                ]
                            ]
                        ],
                        "checked" => $item["checked"] ?? false,
                        "color" => "default"
                    ]
                ];
            }
        }

        // ➤ 加入 children 到 postData（若有內容）
        if (!empty($children)) {
            $postData["children"] = $children;
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
        $priority = $request->input('priority');
        $ticket = $request->input('ticket');
        $description = $request->input('description');
        $todoList = $request->input('todo_list');

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

        if ($priority) {
            $updateData["properties"]["Priority"] = [
                "select" => ["name" => $priority]
            ];
        }

        $headers = $this->getHeaders();

        // 1. 更新 page properties
        $url = $this->notionPagesUrl . '/' . $pageId;
        $response = Http::withHeaders($headers)
            ->withBody(json_encode($updateData), 'application/json')
            ->patch($url);

        // 2. 刪除舊的 to_do / ticket / description blocks
        $childrenListUrl = $this->notionBlocksUrl . '/' . $pageId . '/children';
        $childrenResponse = Http::withHeaders($headers)->get($childrenListUrl);
        $childrenData = $childrenResponse->json();

        if (isset($childrenData["results"])) {
            foreach ($childrenData["results"] as $block) {
                $blockId = $block["id"];
                $type = $block["type"];

                // 刪除 to_do blocks
                if ($type === "to_do") {
                    Http::withHeaders($headers)->delete($this->notionBlocksUrl . '/' . $blockId);
                }

                // 刪除 toggle blocks 標題為 ticket / description
                if ($type === "toggle") {
                    $richText = $block["toggle"]["rich_text"] ?? [];

                    if (!empty($richText) && isset($richText[0]["text"]["content"])) {
                        $titleText = strtolower($richText[0]["text"]["content"]);

                        if (in_array($titleText, ["ticket", "description"])) {
                            Http::withHeaders($headers)->delete($this->notionBlocksUrl . '/' . $blockId);
                        }
                    }
                }
            }
        }

        // 3. 準備新的 children blocks
        $children = [];

        if (!empty($ticket)) {
            $children[] = [
                "object" => "block",
                "type" => "toggle",
                "toggle" => [
                    "rich_text" => [
                        [
                            "type" => "text",
                            "text" => ["content" => "ticket"]
                        ]
                    ],
                    "children" => [
                        [
                            "object" => "block",
                            "type" => "paragraph",
                            "paragraph" => [
                                "rich_text" => [
                                    [
                                        "type" => "text",
                                        "text" => [
                                            "content" => $ticket
                                        ],
                                        "annotations" => [
                                            "bold" => false,
                                            "italic" => false,
                                            "strikethrough" => false,
                                            "underline" => false,
                                            "code" => false,
                                            "color" => "default"
                                        ]
                                    ]
                                ],
                                "color" => "default"
                            ]
                        ]
                    ]
                ]
            ];
        }

        if (!empty($description)) {
            $children[] = [
                "object" => "block",
                "type" => "toggle",
                "toggle" => [
                    "rich_text" => [
                        [
                            "type" => "text",
                            "text" => ["content" => "description"]
                        ]
                    ],
                    "children" => [
                        [
                            "object" => "block",
                            "type" => "paragraph",
                            "paragraph" => [
                                "rich_text" => [
                                    [
                                        "type" => "text",
                                        "text" => [
                                            "content" => $description
                                        ],
                                        "annotations" => [
                                            "bold" => false,
                                            "italic" => false,
                                            "strikethrough" => false,
                                            "underline" => false,
                                            "code" => false,
                                            "color" => "default"
                                        ]
                                    ]
                                ],
                                "color" => "default"
                            ]
                        ]
                    ]
                ]
            ];
        }

        if (is_array($todoList)) {
            foreach ($todoList as $item) {
                $children[] = [
                    "object" => "block",
                    "type" => "to_do",
                    "to_do" => [
                        "rich_text" => [
                            [
                                "type" => "text",
                                "text" => [
                                    "content" => $item["text"] ?? ""
                                ]
                            ]
                        ],
                        "checked" => $item["checked"] ?? false,
                        "color" => "default"
                    ]
                ];
            }
        }

        // 4. append 新的 children blocks
        if (!empty($children)) {
            Http::withHeaders($headers)
                ->withBody(json_encode(["children" => $children]), 'application/json')
                ->patch($childrenListUrl);
        }

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
