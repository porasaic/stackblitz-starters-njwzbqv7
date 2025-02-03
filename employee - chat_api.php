<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';  
require_once __DIR__ . '/../includes/auth.php';


header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success'=>false,'message'=>'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['action'] ?? '';

// Mark user active for “online” presence
$upd = $conn->prepare("UPDATE users SET last_active = NOW() WHERE user_id=?");
$upd->bind_param("i", $user_id);
$upd->execute();
$upd->close();

try {
    switch($method) {
        case 'GET':
            // (1) /employee/chat_api.php?action=online_users => get users & status
            if ($action === 'online_users') {
                $time_window = 10; // show as online if <10 min
                $stmt = $conn->prepare("
                  SELECT username,
                  CASE 
                    WHEN TIMESTAMPDIFF(SECOND, last_active, NOW())<=60 THEN 'Active'
                    WHEN TIMESTAMPDIFF(MINUTE, last_active, NOW())< ? THEN 'Idle'
                    ELSE 'Offline'
                  END AS status
                  FROM users
                  ORDER BY username
                ");
                $stmt->bind_param("i", $time_window);
                $stmt->execute();
                $res = $stmt->get_result();
                $users=[];
                while($row=$res->fetch_assoc()){
                    $users[]=[
                      'username'=>htmlspecialchars($row['username']),
                      'status'=>$row['status']
                    ];
                }
                $stmt->close();
                echo json_encode(['success'=>true,'users'=>$users]);
                exit;
            }
            // (2) /employee/chat_api.php?action=get_typing&channel_id=XX => who is typing
            if ($action==='get_typing') {
                $channel_id = (int)($_GET['channel_id'] ?? 0);
                if ($channel_id<=0) {
                    echo json_encode(['success'=>false,'message'=>'Invalid channel_id']);
                    exit;
                }
                $time_window_seconds = 5;
                $stmt=$conn->prepare("
                  SELECT u.username
                  FROM typing_status ts
                  JOIN users u ON ts.user_id=u.user_id
                  WHERE ts.channel_id=? AND ts.typing=1
                    AND TIMESTAMPDIFF(SECOND, ts.last_updated, NOW())<=?
                    AND ts.user_id!=?
                ");
                $stmt->bind_param("iii", $channel_id, $time_window_seconds, $user_id);
                $stmt->execute();
                $res=$stmt->get_result();
                $typing_users=[];
                while($row=$res->fetch_assoc()){
                    $typing_users[]=['username'=>htmlspecialchars($row['username'])];
                }
                $stmt->close();
                echo json_encode(['success'=>true,'typing_users'=>$typing_users]);
                exit;
            }
            // (3) /employee/chat_api.php?channel_id=XX => load messages
            $channel_id=(int)($_GET['channel_id'] ?? 0);
            if($channel_id<=0){
                echo json_encode(['success'=>false,'message'=>'No channel_id provided']);
                exit;
            }
            $stmt=$conn->prepare("
              SELECT cm.message_id, cm.message_text AS text, cm.created_at, u.username
              FROM chat_messages cm
              JOIN users u ON cm.user_id=u.user_id
              WHERE cm.channel_id=?
              ORDER BY cm.created_at ASC
            ");
            $stmt->bind_param("i",$channel_id);
            $stmt->execute();
            $res=$stmt->get_result();
            $messages=[];
            while($row=$res->fetch_assoc()){
                $messages[]=[
                  'message_id'=>$row['message_id'],
                  'username'=>htmlspecialchars($row['username']),
                  'text'=>htmlspecialchars($row['text']),
                  'timestamp'=>$row['created_at']
                ];
            }
            $stmt->close();
            echo json_encode(['success'=>true,'messages'=>$messages]);
            break;

        case 'POST':
            $input=json_decode(file_get_contents('php://input'),true);
            $postAction=$input['action'] ?? 'send_message';
            if($postAction==='typing'){
                // track typing
                $channel_id=(int)($input['channel_id']??0);
                $typing= !empty($input['typing'])?1:0;
                if($channel_id<=0){
                    echo json_encode(['success'=>false,'message'=>'Invalid channel_id']);
                    exit;
                }
                $stmt=$conn->prepare("
                  INSERT INTO typing_status (user_id, channel_id, typing, last_updated)
                  VALUES (?,?,?,NOW())
                  ON DUPLICATE KEY UPDATE typing=VALUES(typing), last_updated=NOW()
                ");
                $stmt->bind_param("iii",$user_id,$channel_id,$typing);
                if($stmt->execute()){
                    echo json_encode(['success'=>true]);
                }else{
                    echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
                }
                $stmt->close();
                exit;
            }
            elseif($postAction==='send_message'){
                $channel_id=(int)($input['channel_id']??0);
                $msg=trim($input['message']??'');
                if($channel_id<=0 || $msg===''){
                    echo json_encode(['success'=>false,'message'=>'Empty or invalid data']);
                    exit;
                }
                $stmt=$conn->prepare("
                  INSERT INTO chat_messages (channel_id, user_id, message_text) 
                  VALUES (?,?,?)
                ");
                $stmt->bind_param("iis",$channel_id,$user_id,$msg);
                if($stmt->execute()){
                    echo json_encode(['success'=>true]);
                } else{
                    echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
                }
                $stmt->close();
                exit;
            }
            else {
                echo json_encode(['success'=>false,'message'=>'Unsupported POST action']);
            }
            break;

        default:
            echo json_encode(['success'=>false,'message'=>'Unsupported request method']);
            break;
    }
} catch(Exception $ex){
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server exception: '.$ex->getMessage()]);
}
