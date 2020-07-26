<?php
ini_set('log_errors','on');
ini_set('error_log','php.log');
session_start();

// トレーナーを格納
$trainer = array();
// トレーナーの筋肉クラス
class KINNIKU{
    const BODYBILL = 1;
    const MATTYO = 2;
    const COMMON = 3;
    const GARI = 4;
}
// 抽象クラス（生き物クラス）
abstract class Creature{
    protected $name;
    protected $hp;
    protected $attackMin;
    protected $attackMax;
    abstract public function sayCry();
    public function setName($str){
        $this->name = $str;
    }
    public function getName(){
        return $this->name;
    }
    public function setHp($num){
        $this->hp = $num;
    }
    public function getHp(){
        return $this->hp;
    }
    public function attack($targetObj){
        $attackPoint = mt_rand($this->attackMin, $this->attackMax);
        // 10分の1の確率で火事場の馬鹿力
        if(!mt_rand(0,9)){
            $attackPoint = $attackPoint * 2.0;
            $attackPoint = (int)$attackPoint;
            History::set($this->getName().'の火事場の馬鹿力！！');
        }
        $targetObj->setHp($targetObj->getHp()-$attackPoint);
        History::set($attackPoint.'ポイントのダメージ！');
    }
}
// 人クラス
class Human extends Creature{
    protected $kinniku;
    public function __construct($name,$kinniku,$hp,$attackMin,$attackMax){
        $this->name = $name;
        $this->kinniku = $kinniku;
        $this->hp = $hp;
        $this->attackMin = $attackMin;
        $this->attackMax = $attackMax;
    }
    public function setKinniku($num){
        $this->kinniku = $num;
    }
    public function getKinniku(){
        return $this->kinniku;
    }
    public function sayCry(){
        History::set($this->name.'が声を上げる！！');
        switch($this->kinniku){
            case Kinniku::BODYBILL :
                History::set('え？こんなもん？');
            break;
            case Kinniku::MATTYO :
                History::set('もっと負荷を頂戴！♡');
            break;
            case Kinniku::COMMON :
                History::set('ぐはっ！');
            break;
            case Kinniku::GARI :
                History::set('グァあああ！');
            break;
        }
    }
}
// トレーナークラス
class Trainer extends Creature{
    protected $img;

    public function __construct($name,$hp,$img,$attackMin,$attackMax){
        $this->name = $name;
        $this->hp = $hp;
        $this->img = $img;
        $this->attackMin = $attackMin;
        $this->attackMax = $attackMax;
    }

    public function getImg(){
        return $this->img;
    }
    public function sayCry(){
        History::set($this->name.'：まだまだ追い込めよ！！');
    }
}

class SuperSet extends Trainer{
    private $superset;
    function __construct($name,$hp,$img,$attackMin,$attackMax,$superset){
        parent::__construct($name,$hp,$img,$attackMin,$attackMax);
        $this->superset = $superset;
    }
    public function getsuperset(){
        return $this->superset;
    }
    public function attack($targetObj){
        if(!mt_rand(0,4)){
            History::set($this->name.'のスーパーセットメニュー！');
            $targetObj->setHp($targetObj->getHp() - $this->superset);
            History::set($this->superset.'ポイントの負荷を受けた！');
        }else{
            parent::attack($targetObj);
        }
    }
}
interface HistoryInterface{
    public static function set($str);
    public static function clear();
}
class History implements HistoryInterface{
    public static function set($str){
        if(empty($_SESSION['history'])) $_SESSION['history'] = '';
        $_SESSION['history'].=$str.'<br>';
    }
    public static function clear(){
        unset($_SESSION['history']);
    }
}
// インスタンス生成
$human = new Human('トレーニング初心者',KINNIKU::GARI,100,20,30);
// $trainers[] = new Trainer('トレーニング好きのおじちゃん',50,'img/img01.png', 20 , 10 );
$trainers[] = new Trainer( '健康志向のおじいちゃん', 50, 'img/trainer5.jpeg', 10, 20 );
$trainers[] = new Trainer( 'イキリ大学生', 80, 'img/trainer4.jpeg', 20, 30 );
// $trainers[] = new Trainer('イキリの学生',80,'img/img2.png', 30 , 10 );
$trainers[] = new SuperSet('ボディビルダーの男',100,'img/trainer1.jpeg',40,50, mt_rand(80,100));


function createTrainer(){
    global $trainers;
    $trainer = $trainers[mt_rand(0,2)];
    History::set($trainer->getName().'が現れた！');
    $_SESSION['trainer'] = $trainer;
}
function createHuman(){
    global $human;
    $_SESSION['human'] = $human;
}
function init(){
    History::clear();
    History::set('初期化します。');
    $_SESSION['knockDownCount'] = 0;
    createHuman();
    createTrainer();
}
function gameOver(){
    $_SESSION = array();
}

// 1.post送信されていた場合
if(!empty($_POST)){
    $attackFlg = (!empty($_POST['attack'])) ? true  : false;
    $startFlg = (!empty($_POST['start'])) ? true : false;
    // $drinkFlg = (!empyt($_POST['drink'])) ? true : false;
    error_log('POSTされた！');

    if($startFlg){
        History::set('ゲームスタート！');
        init();
    }else{
        if($attackFlg){
            // トレーナーのメニューを行う
            History::clear();
            History::set($_SESSION['human']->getName().'がトレーニングを行う');
            $_SESSION['human']->attack($_SESSION['trainer']);
            $_SESSION['trainer']->sayCry();
            // hpにダメージを受ける
            // History::clear();
            History::set($_SESSION['trainer']->getName().'のメニュー');
            $_SESSION['trainer']->attack($_SESSION['human']);
            $_SESSION['human']->sayCry();

            // 自分のhpが０以下になったらゲームオーバー
            if($_SESSION['human']->getHp() <= 0){
                gameOver();
            }else{
                if($_SESSION['trainer']->getHp() <= 0){
                    History::set($_SESSION['trainer']->getName().'のメニューを突破した！');
                    createTrainer();
                    $_SESSION['knockDownCount'] = $_SESSION['knockDownCount']+1;
                }
            }
        // }elseif($drinkFlg){          
        //     History::set($_SESSION['human']->getName().'がワークアウトドリングを飲んだ！');
        //         $_SESSION['human']->getHp()+10;
        }else{
            History::clear();
            History::set('逃げた！');
            createTrainer();
        }
    }
    $_POST = array();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>タイトル</title>
    <script src="https://code.jquery.com/jquery-3.3.1.js"></script>
    <style>
    	body{
	    	margin: 0 auto;
	    	padding: 150px;
	    	width: 75%;
	    	background: #fbfbfa;
        color: white;
    	}
    	h1{ color: white; font-size: 20px; text-align: center;}
      h2{ color: white; font-size: 16px; text-align: center;}
    	form{
	    	overflow: hidden;
    	}
    	input[type="text"]{
    		color: #545454;
	    	height: 60px;
	    	width: 100%;
	    	padding: 5px 10px;
	    	font-size: 16px;
	    	display: block;
	    	margin-bottom: 10px;
	    	box-sizing: border-box;
    	}
      input[type="password"]{
    		color: #545454;
	    	height: 60px;
	    	width: 100%;
	    	padding: 5px 10px;
	    	font-size: 16px;
	    	display: block;
	    	margin-bottom: 10px;
	    	box-sizing: border-box;
    	}
    	input[type="submit"]{
	    	border: none;
	    	padding: 15px 30px;
	    	margin-bottom: 15px;
	    	background: black;
	    	color: white;
	    	float: right;
    	}
    	input[type="submit"]:hover{
	    	background: #3d3938;
	    	cursor: pointer;
    	}
    	a{
	    	color: #545454;
	    	display: block;
    	}
    	a:hover{
	    	text-decoration: none;
    	}
        .hpGauge{
            border: 1px,solid,#777;
            margin-top: 10px;
        }
        .hpGaugeValue{
            height: 15px;
            background-color: #6bf;  
        }
        
        
    </style>
</head>
<body>
    <h1 style="text-align:center; color:#333;">ゲーム「合トレ!!」</h1>
    <div style="background:black; padding:15px; position:relative;">
        <?php if(empty($_SESSION)){ ?>
            <h2 style="margin-top:60px;">GAME START ?</h2>
            <form method="post">
            <input type="submit" name="start" value="▶︎ゲームスタート">
            </form>
        <?php }else{ ?>
        <h2><?php echo $_SESSION['trainer']->getName().'が現れた！！';?></h2>
            <p style="text-align:center;">合トレを行った数:<?php echo $_SESSION['knockDownCount']; ?></p>
        <div class="player_state" style="width:30%; display:inline-block; margin-right:180px; margin-left:80px;">
            
            <div class="hpGauge">
                <div class="hpGaugeValue"　id="player_hp" style="width:<?php echo $_SESSION['human']->getHp();?>%;"></div>
            </div>
            <p style="font-size:14px; text-align:center;">自分の残りエネルギー:<?php echo $_SESSION['human']->getHp(); ?></p>
        </div>
        <div class="trainer_state" style="width:30%; display:inline-block;">
            <div style="height: 150px;　width:200px;">
                <img src="<?php echo $_SESSION['trainer']->getImg(); ?>" alt="" style="height: 150px;　width:200px;">
            </div>
            <div class="hpGauge">
                    <div class="hpGaugeValue" id="trainer_hp" style="width:<?php echo $_SESSION['trainer']->getHp();?>%;"></div>
                </div>
            <p style="font-size:14px; text-align:center;">トレーナーのHP:<?php echo $_SESSION['trainer']->getHp(); ?></p>
        </div>
            <div>
                <p style ="width:300px; text-align: center; margin-left: auto; margin-right: auto;"><?php echo (!empty($_SESSION['history'])) ? $_SESSION['history'] : '';?></p>
            </div>
            <form method="post">
                <input type="submit" name="start" value="▶︎ゲームリスタート">
                <input type="submit" name="escape" value="▶︎逃げる">
                <input type="submit" name="drink" value="▶︎ワークアウトドリンクを飲む">
                <input type="submit" name="attack" value="▶︎メニューをこなす">
            </form>
            <?php } ?>
    </div>

   
    
</body>
</html>