<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessExceptions\ParamsErrorException;
use App\Exceptions\BusinessExceptions\UnavailableException;
use App\Exceptions\BusinessExceptions\UnknownException;
use App\Models\Tester;
use App\Services\MediaService;
use App\Services\ThirdApi\OpenPlatformService;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Str;

class SubMiniProgramController extends Controller
{
    /**
     * 获取小程序实例
     * @return \EasyWeChat\OpenPlatform\Authorizer\MiniProgram\Application
     * @throws UnknownException
     */
    private function getMiniProgramApplication(): \EasyWeChat\OpenPlatform\Authorizer\MiniProgram\Application
    {
        $appId = $this->getAppId();
        $openPlatformModel = request()->attributes->get('openPlatform');
        $openPlatform = new OpenPlatformService($openPlatformModel);
        $result = $openPlatform->getAuthorizer($appId);
        if (!array_key_exists('authorization_info', $result)) {
            if ($result['errcode'] != 0) {
                throw new UnknownException($result['errmsg']);
            }
        }
        $refreshToken = $result['authorization_info']['authorizer_refresh_token'];
        // 生成实例，代小程序实现业务
        return $openPlatform->miniProgram($appId, $refreshToken);
    }

    private function getAppId()
    {
        return request()->route('appId');
    }

    /**
     * 获取账户基本信息
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws UnknownException
     */
    public function basicInfo()
    {
        $application = $this->getMiniProgramApplication();
        return $application->account->getBasicInfo();
    }

    /**
     * 上传到本地文件
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws UnavailableException
     * @throws ParamsErrorException
     */
    public function uploadLocalMedia(): \Illuminate\Http\JsonResponse
    {
        $files = request()->allFiles();
        $type = request()->input('type');
        if (!in_array($type, ['image', 'video', 'voice'])) {
            throw new ParamsErrorException();
        }
        $mediaService = new MediaService();
        $count = 0;
        foreach ($files as $file) {
            $result = $mediaService->upload($this->getAppId(), $file, $type);
            if ($result) {
                $count++;
            }
        }
        if ($count == 0) {
            throw new UnavailableException('文件上传失败');
        }
        return response()->json(['success' => $count]);
    }

    /**
     * 删除到本地文件
     *
     * @throws ParamsErrorException
     */
    public function deleteLocalMedia(): string
    {
        $fileNames = request()->input('fileNames');
        $type = request()->input('type');
        if (!in_array($type, ['image', 'video', 'voice'])) {
            throw new ParamsErrorException();
        }
        $mediaService = new MediaService();
        $result = $mediaService->deleteFiles($this->getAppId(), $fileNames, $type);
        return response()->json(['success' => $result]);
    }


    /**
     * @throws \Throwable
     */
    public function getLocalMediaList(): array
    {
        $mediaService = new MediaService();
        $type = request()->query('type');
        $list = $mediaService->allFiles($this->getAppId(), $type);
        $resultArray = [
            'image' => [],
            'video' => [],
            'voice' => []
        ];
        $key = config('custom.media_token_cache_prefix').$this->getAppId();
        $token = cache()->get($key);
        if (!$token) {
            $token = Str::random();
            cache()->set($key, $token, config('custom.media_token_cache_ttl') * 60);
        }
        foreach ($list as $item) {
            $itemNameArray = explode('/', $item);
            $type = $itemNameArray[count($itemNameArray) - 2];
            $name = $itemNameArray[count($itemNameArray) - 1];
            $url = route("platform-media",
                    ['appId' => $this->getAppId(), 'type' => $type, 'fileName' => $name]).'?token='.$token;
            $resultArray[$type][] = ['url' => $url, 'name' => $name];
        }
        return $resultArray;
    }

    /**
     * 上传临时文件素材
     *
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \Throwable
     */
    public function uploadTemplateMedia()
    {
        $fileName = request()->input('fileName');
        $type = request()->input('type');
        if (!in_array($type, ['image', 'video', 'voice'])) {
            throw new ParamsErrorException();
        }
        $miniProgram = $this->getMiniProgramApplication();
        $mediaService = new MediaService();
        $file = $mediaService->getFilePath($this->getAppId(), $fileName, $type);
        return $miniProgram->media->upload($type, $file);
    }

    /**
     * 检测名称是否可用
     *
     * @throws \Throwable
     */
    public function checkNickName()
    {
        $nickName = request()->input('nick_name');
        $miniProgram = $this->getMiniProgramApplication();
        return $miniProgram->setting->isAvailableNickname($nickName);
    }

    /**
     * 获取改名结果
     *
     * @throws \Throwable
     */
    public function getNicknameAuditStatus()
    {
        $auditId = request()->input('audit_id');
        $miniProgram = $this->getMiniProgramApplication();
        return $miniProgram->setting->getNicknameAuditStatus($auditId);
    }

    /**
     * 设置名称
     *
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \Throwable
     */
    public function setNickName()
    {
        $nickName = request()->input('nick_name');
        $idCard = request()->input('id_card');
        $license = request()->input('license');
        $otherMediaArray = [];
        $other1 = request()->input('naming_other_stuff_1');
        $other2 = request()->input('naming_other_stuff_2');
        $other3 = request()->input('naming_other_stuff_3');
        $other4 = request()->input('naming_other_stuff_4');
        $other5 = request()->input('naming_other_stuff_5');
        if ($other1) {
            $otherMediaArray[] = $other1;
        }
        if ($other2) {
            $otherMediaArray[] = $other2;
        }
        if ($other3) {
            $otherMediaArray[] = $other3;
        }
        if ($other4) {
            $otherMediaArray[] = $other4;
        }
        if ($other5) {
            $otherMediaArray[] = $other5;
        }
        $miniProgram = $this->getMiniProgramApplication();
        if ($license) {
            $response = $miniProgram->setting->setNickname($nickName, '', $license, $otherMediaArray);
        } else {
            $response = $miniProgram->setting->setNickname($nickName, $idCard, '', $otherMediaArray);
        }
        return $response;
    }

    /**
     * 设置头像
     *
     * @throws InvalidConfigException
     * @throws GuzzleException
     * @throws UnknownException
     */
    public function setAvatar()
    {
        $miniProgram = $this->getMiniProgramApplication();
        $head_img_media_id = request()->input('head_img_media_id');
        $x1 = request()->input('x1');
        $y1 = request()->input('y1');
        $x2 = request()->input('x2');
        $y2 = request()->input('y2');
        if ($x1 || $x2 || $y1 || $y2) {
            return $miniProgram->account->updateAvatar($head_img_media_id, $x1, $y1, $x2, $y2);
        }
        return $miniProgram->account->updateAvatar($head_img_media_id);
    }

    /**
     * 设置简介
     *
     * @throws InvalidConfigException
     * @throws GuzzleException
     * @throws UnknownException
     */
    public function setSignature()
    {
        $miniProgram = $this->getMiniProgramApplication();
        $signature = request()->input('signature');
        return $miniProgram->account->updateSignature($signature);
    }

    /**
     * 获取所有小程序体验者
     *
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws GuzzleException|InvalidConfigException|UnknownException
     */
    public function testers()
    {
        $miniProgram = $this->getMiniProgramApplication();
        $testers = Tester::query()->where('app_id', $this->getAppId())->get();
        $result = $miniProgram->tester->list();
        if ($result['errcode'] != 0) {
            return $result;
        }
        $list = $result['members'];
        foreach ($list as &$item) {
            $userStr = $item['userstr'];
            /** @var Tester $tester */
            $tester = $testers->where('user_str', $userStr)->first();
            unset($item['userstr']);
            $item['user_str'] = $userStr;
            if ($tester) {
                $item['wechat_id'] = $tester->wechat_id;
            }
        }
        return $list;
    }

    /**
     * 添加小程序体验者
     *
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws GuzzleException
     * @throws InvalidConfigException|UnknownException
     */
    public function bindTester()
    {
        $wechatId = request('wechatId');
        $miniProgram = $this->getMiniProgramApplication();
        $result = $miniProgram->tester->bind($wechatId);
        if ($result['errcode'] == 0) {
            $userStr = $result['userstr'];
            $tester = Tester::query()->where('wechat_id', $wechatId)->where('app_id', $this->getAppId())->first();
            if (!$tester) {
                $tester = new Tester();
                $tester->app_id = $this->getAppId();
                $tester->user_str = $userStr;
                $tester->wechat_id = $wechatId;
            } else {
                $tester->user_str = $userStr;
            }
            $tester->save();
        }
        return $result;
    }

    /**
     * 解绑小程序体验者
     *
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws GuzzleException
     * @throws InvalidConfigException
     * @throws UnknownException
     */
    public function unBindTester()
    {
        $useWechatId = request()->query->get('useWechatId');
        $userSlug = request()->route('userSlug');
        $miniProgram = $this->getMiniProgramApplication();
        if ($useWechatId) {
            $result = $miniProgram->tester->unbind($userSlug);
            if ($result['errcode'] == 0) {
                $tester = Tester::query()->where('wechat_id', $userSlug)->where('app_id', $this->getAppId())->first();
                if ($tester) {
                    $tester->delete();
                }
            }
        } else {
            $result = $miniProgram->tester->unbind(null, $userSlug);
            if ($result['errcode'] == 0) {
                $tester = Tester::query()->where('user_str', $userSlug)->where('app_id', $this->getAppId())->first();
                if ($tester) {
                    $tester->delete();
                }
            }
        }
        return $result;
    }
}
