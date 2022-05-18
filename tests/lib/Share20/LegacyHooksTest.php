<?php
/**
 * @copyright 2017, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Share20;

use OC\Share20\LegacyHooks;
use OC\Share20\Manager;
use OCP\Constants;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\File;
use OCP\Share\IShare;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;
use Test\TestCase;

class LegacyHooksTest extends TestCase {

	/** @var LegacyHooks */
	private $hooks;

	/** @var EventDispatcher */
	private $eventDispatcher;

	/** @var Manager */
	private $manager;

	protected function setUp(): void {
		parent::setUp();

		$this->eventDispatcher = new EventDispatcher();
		$this->hooks = new LegacyHooks($this->eventDispatcher);
		$this->manager = \OC::$server->getShareManager();
	}

	public function testPreUnshare() {
		$path = $this->createMock(File::class);
		$path->method('getId')->willReturn(1);

		$info = $this->createMock(ICacheEntry::class);
		$info->method('getMimeType')->willReturn('text/plain');

		$share = $this->manager->newShare();
		$share->setId(42)
			->setProviderId('prov')
			->setShareType(IShare::TYPE_USER)
			->setSharedWith('awesomeUser')
			->setSharedBy('sharedBy')
			->setNode($path)
			->setTarget('myTarget')
			->setNodeCacheEntry($info);

		$hookListner = $this->getMockBuilder('Dummy')->setMethods(['pre'])->getMock();
		\OCP\Util::connectHook('OCP\Share', 'pre_unshare', $hookListner, 'pre');

		$hookListnerExpectsPre = [
			'id' => 42,
			'itemType' => 'file',
			'itemSource' => 1,
			'shareType' => IShare::TYPE_USER,
			'shareWith' => 'awesomeUser',
			'itemparent' => null,
			'uidOwner' => 'sharedBy',
			'fileSource' => 1,
			'fileTarget' => 'myTarget',
		];

		$hookListner
			->expects($this->exactly(1))
			->method('pre')
			->with($hookListnerExpectsPre);

		$event = new GenericEvent($share);
		$this->eventDispatcher->dispatch('OCP\Share::preUnshare', $event);
	}

	public function testPostUnshare() {
		$path = $this->createMock(File::class);
		$path->method('getId')->willReturn(1);

		$info = $this->createMock(ICacheEntry::class);
		$info->method('getMimeType')->willReturn('text/plain');

		$share = $this->manager->newShare();
		$share->setId(42)
			->setProviderId('prov')
			->setShareType(IShare::TYPE_USER)
			->setSharedWith('awesomeUser')
			->setSharedBy('sharedBy')
			->setNode($path)
			->setTarget('myTarget')
			->setNodeCacheEntry($info);

		$hookListner = $this->getMockBuilder('Dummy')->setMethods(['post'])->getMock();
		\OCP\Util::connectHook('OCP\Share', 'post_unshare', $hookListner, 'post');

		$hookListnerExpectsPost = [
			'id' => 42,
			'itemType' => 'file',
			'itemSource' => 1,
			'shareType' => IShare::TYPE_USER,
			'shareWith' => 'awesomeUser',
			'itemparent' => null,
			'uidOwner' => 'sharedBy',
			'fileSource' => 1,
			'fileTarget' => 'myTarget',
			'deletedShares' => [
				[
					'id' => 42,
					'itemType' => 'file',
					'itemSource' => 1,
					'shareType' => IShare::TYPE_USER,
					'shareWith' => 'awesomeUser',
					'itemparent' => null,
					'uidOwner' => 'sharedBy',
					'fileSource' => 1,
					'fileTarget' => 'myTarget',
				],
			],
		];

		$hookListner
			->expects($this->exactly(1))
			->method('post')
			->with($hookListnerExpectsPost);

		$event = new GenericEvent($share);
		$event->setArgument('deletedShares', [$share]);
		$this->eventDispatcher->dispatch('OCP\Share::postUnshare', $event);
	}

	public function testPostUnshareFromSelf() {
		$path = $this->createMock(File::class);
		$path->method('getId')->willReturn(1);

		$info = $this->createMock(ICacheEntry::class);
		$info->method('getMimeType')->willReturn('text/plain');

		$share = $this->manager->newShare();
		$share->setId(42)
			->setProviderId('prov')
			->setShareType(IShare::TYPE_USER)
			->setSharedWith('awesomeUser')
			->setSharedBy('sharedBy')
			->setNode($path)
			->setTarget('myTarget')
			->setNodeCacheEntry($info);

		$hookListner = $this->getMockBuilder('Dummy')->setMethods(['postFromSelf'])->getMock();
		\OCP\Util::connectHook('OCP\Share', 'post_unshareFromSelf', $hookListner, 'postFromSelf');

		$hookListnerExpectsPostFromSelf = [
			'id' => 42,
			'itemType' => 'file',
			'itemSource' => 1,
			'shareType' => IShare::TYPE_USER,
			'shareWith' => 'awesomeUser',
			'itemparent' => null,
			'uidOwner' => 'sharedBy',
			'fileSource' => 1,
			'fileTarget' => 'myTarget',
			'itemTarget' => 'myTarget',
			'unsharedItems' => [
				[
					'id' => 42,
					'itemType' => 'file',
					'itemSource' => 1,
					'shareType' => IShare::TYPE_USER,
					'shareWith' => 'awesomeUser',
					'itemparent' => null,
					'uidOwner' => 'sharedBy',
					'fileSource' => 1,
					'fileTarget' => 'myTarget',
					'itemTarget' => 'myTarget',
				],
			],
		];

		$hookListner
			->expects($this->exactly(1))
			->method('postFromSelf')
			->with($hookListnerExpectsPostFromSelf);

		$event = new GenericEvent($share);
		$this->eventDispatcher->dispatch('OCP\Share::postUnshareFromSelf', $event);
	}

	public function testPreShare() {
		$path = $this->createMock(File::class);
		$path->method('getId')->willReturn(1);

		$date = new \DateTime();

		$share = $this->manager->newShare();
		$share->setShareType(IShare::TYPE_LINK)
			->setSharedWith('awesomeUser')
			->setSharedBy('sharedBy')
			->setNode($path)
			->setTarget('myTarget')
			->setPermissions(Constants::PERMISSION_ALL)
			->setExpirationDate($date)
			->setPassword('password')
			->setToken('token');


		$hookListner = $this->getMockBuilder('Dummy')->setMethods(['preShare'])->getMock();
		\OCP\Util::connectHook('OCP\Share', 'pre_shared', $hookListner, 'preShare');

		$run = true;
		$error = '';

		$expected = [
			'itemType' => 'file',
			'itemSource' => 1,
			'shareType' => IShare::TYPE_LINK,
			'shareWith' => 'awesomeUser',
			'uidOwner' => 'sharedBy',
			'fileSource' => 1,
			'itemTarget' => 'myTarget',
			'permissions' => Constants::PERMISSION_ALL,
			'expiration' => $date,
			'token' => 'token',
			'run' => &$run,
			'error' => &$error,
		];

		$hookListner
			->expects($this->exactly(1))
			->method('preShare')
			->with($expected);

		$event = new GenericEvent($share);
		$this->eventDispatcher->dispatch('OCP\Share::preShare', $event);
	}

	public function testPreShareError() {
		$path = $this->createMock(File::class);
		$path->method('getId')->willReturn(1);

		$date = new \DateTime();

		$share = $this->manager->newShare();
		$share->setShareType(IShare::TYPE_LINK)
			->setSharedWith('awesomeUser')
			->setSharedBy('sharedBy')
			->setNode($path)
			->setTarget('myTarget')
			->setPermissions(Constants::PERMISSION_ALL)
			->setExpirationDate($date)
			->setPassword('password')
			->setToken('token');


		$hookListner = $this->getMockBuilder('Dummy')->setMethods(['preShare'])->getMock();
		\OCP\Util::connectHook('OCP\Share', 'pre_shared', $hookListner, 'preShare');

		$run = true;
		$error = '';

		$expected = [
			'itemType' => 'file',
			'itemSource' => 1,
			'shareType' => IShare::TYPE_LINK,
			'shareWith' => 'awesomeUser',
			'uidOwner' => 'sharedBy',
			'fileSource' => 1,
			'itemTarget' => 'myTarget',
			'permissions' => Constants::PERMISSION_ALL,
			'expiration' => $date,
			'token' => 'token',
			'run' => &$run,
			'error' => &$error,
		];

		$hookListner
			->expects($this->exactly(1))
			->method('preShare')
			->with($expected)
			->willReturnCallback(function ($data) {
				$data['run'] = false;
				$data['error'] = 'I error';
			});

		$event = new GenericEvent($share);
		$this->eventDispatcher->dispatch('OCP\Share::preShare', $event);

		$this->assertTrue($event->isPropagationStopped());
		$this->assertSame('I error', $event->getArgument('error'));
	}

	public function testPostShare() {
		$path = $this->createMock(File::class);
		$path->method('getId')->willReturn(1);

		$date = new \DateTime();

		$share = $this->manager->newShare();
		$share->setId(42)
			->setShareType(IShare::TYPE_LINK)
			->setSharedWith('awesomeUser')
			->setSharedBy('sharedBy')
			->setNode($path)
			->setTarget('myTarget')
			->setPermissions(Constants::PERMISSION_ALL)
			->setExpirationDate($date)
			->setPassword('password')
			->setToken('token');


		$hookListner = $this->getMockBuilder('Dummy')->setMethods(['postShare'])->getMock();
		\OCP\Util::connectHook('OCP\Share', 'post_shared', $hookListner, 'postShare');

		$expected = [
			'id' => 42,
			'itemType' => 'file',
			'itemSource' => 1,
			'shareType' => IShare::TYPE_LINK,
			'shareWith' => 'awesomeUser',
			'uidOwner' => 'sharedBy',
			'fileSource' => 1,
			'itemTarget' => 'myTarget',
			'fileTarget' => 'myTarget',
			'permissions' => Constants::PERMISSION_ALL,
			'expiration' => $date,
			'token' => 'token',
			'path' => null,
		];

		$hookListner
			->expects($this->exactly(1))
			->method('postShare')
			->with($expected);

		$event = new GenericEvent($share);
		$this->eventDispatcher->dispatch('OCP\Share::postShare', $event);
	}

	public function providesDataForCanGet() {
		// normal file (sender) - can download directly
		$senderFileStorage = $this->createMock(IStorage::class);
		$senderFileStorage->method('instanceOfStorage')->with(SharedStorage::class)->willReturn(false);
		$senderFile = $this->createMock(File::class);
		$senderFile->method('getStorage')->willReturn($senderFileStorage);
		$senderUserFolder = $this->createMock(Folder::class);
		$senderUserFolder->method('get')->willReturn($senderFile);

		$result[] = [ '/bar.txt', $senderUserFolder, true ];

		// shared file (receiver) with attribute secure-view-enabled set false -
		// can download directly
		$receiverFileShareAttributes = $this->createMock(IAttributes::class);
		$receiverFileShareAttributes->method('getAttribute')->with('permissions', 'download')->willReturn(true);
		$receiverFileShare = $this->createMock(IShare::class);
		$receiverFileShare->method('getAttributes')->willReturn($receiverFileShareAttributes);
		$receiverFileStorage = $this->createMock(SharedStorage::class);
		$receiverFileStorage->method('instanceOfStorage')->with(SharedStorage::class)->willReturn(true);
		$receiverFileStorage->method('getShare')->willReturn($receiverFileShare);
		$receiverFile = $this->createMock(File::class);
		$receiverFile->method('getStorage')->willReturn($receiverFileStorage);
		$receiverUserFolder = $this->createMock(Folder::class);
		$receiverUserFolder->method('get')->willReturn($receiverFile);

		$result[] = [ '/share-bar.txt', $receiverUserFolder, true ];

		// shared file (receiver) with attribute secure-view-enabled set true -
		// cannot download directly
		$secureReceiverFileShareAttributes = $this->createMock(IAttributes::class);
		$secureReceiverFileShareAttributes->method('getAttribute')->with('permissions', 'download')->willReturn(false);
		$secureReceiverFileShare = $this->createMock(IShare::class);
		$secureReceiverFileShare->method('getAttributes')->willReturn($secureReceiverFileShareAttributes);
		$secureReceiverFileStorage = $this->createMock(SharedStorage::class);
		$secureReceiverFileStorage->method('instanceOfStorage')->with(SharedStorage::class)->willReturn(true);
		$secureReceiverFileStorage->method('getShare')->willReturn($secureReceiverFileShare);
		$secureReceiverFile = $this->createMock(File::class);
		$secureReceiverFile->method('getStorage')->willReturn($secureReceiverFileStorage);
		$secureReceiverUserFolder = $this->createMock(Folder::class);
		$secureReceiverUserFolder->method('get')->willReturn($secureReceiverFile);

		$result[] = [ '/secure-share-bar.txt', $secureReceiverUserFolder, false ];

		return $result;
	}

	/**
	 * @dataProvider providesDataForCanGet
	 */
	public function testCheckDirectCanBeDownloaded($path, $userFolder, $run) {
		$user = $this->createMock(IUser::class);
		$user->method("getUID")->willReturn("test");
		$this->userSession->method("getUser")->willReturn($user);
		$this->userSession->method("isLoggedIn")->willReturn(true);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		// Simulate direct download of file
		$event = new GenericEvent(null, [ 'path' => $path ]);
		$this->eventDispatcher->dispatch('file.beforeGetDirect', $event);

		$this->assertEquals($run, !$event->hasArgument('errorMessage'));
	}

	public function providesDataForCanZip() {
		// Mock: Normal file/folder storage
		$nonSharedStorage = $this->createMock(IStorage::class);
		$nonSharedStorage->method('instanceOfStorage')->with(SharedStorage::class)->willReturn(false);

		// Mock: Secure-view file/folder shared storage
		$secureReceiverFileShareAttributes = $this->createMock(IAttributes::class);
		$secureReceiverFileShareAttributes->method('getAttribute')->with('permissions', 'download')->willReturn(false);
		$secureReceiverFileShare = $this->createMock(IShare::class);
		$secureReceiverFileShare->method('getAttributes')->willReturn($secureReceiverFileShareAttributes);
		$secureSharedStorage = $this->createMock(SharedStorage::class);
		$secureSharedStorage->method('instanceOfStorage')->with(SharedStorage::class)->willReturn(true);
		$secureSharedStorage->method('getShare')->willReturn($secureReceiverFileShare);

		// 1. can download zipped 2 non-shared files inside non-shared folder
		// 2. can download zipped non-shared folder
		$sender1File = $this->createMock(File::class);
		$sender1File->method('getStorage')->willReturn($nonSharedStorage);
		$sender1Folder = $this->createMock(Folder::class);
		$sender1Folder->method('getStorage')->willReturn($nonSharedStorage);
		$sender1Folder->method('getDirectoryListing')->willReturn([$sender1File, $sender1File]);
		$sender1RootFolder = $this->createMock(Folder::class);
		$sender1RootFolder->method('getStorage')->willReturn($nonSharedStorage);
		$sender1RootFolder->method('getDirectoryListing')->willReturn([$sender1Folder]);
		$sender1UserFolder = $this->createMock(Folder::class);
		$sender1UserFolder->method('get')->willReturn($sender1RootFolder);

		$return[] = [ '/folder', ['bar1.txt', 'bar2.txt'], $sender1UserFolder, true ];
		$return[] = [ '/', 'folder', $sender1UserFolder, true ];

		// 3. cannot download zipped 1 non-shared file and 1 secure-shared inside non-shared folder
		$receiver1File = $this->createMock(File::class);
		$receiver1File->method('getStorage')->willReturn($nonSharedStorage);
		$receiver1SecureFile = $this->createMock(File::class);
		$receiver1SecureFile->method('getStorage')->willReturn($secureSharedStorage);
		$receiver1Folder = $this->createMock(Folder::class);
		$receiver1Folder->method('getStorage')->willReturn($nonSharedStorage);
		$receiver1Folder->method('getDirectoryListing')->willReturn([$receiver1File, $receiver1SecureFile]);
		$receiver1RootFolder = $this->createMock(Folder::class);
		$receiver1RootFolder->method('getStorage')->willReturn($nonSharedStorage);
		$receiver1RootFolder->method('getDirectoryListing')->willReturn([$receiver1Folder]);
		$receiver1UserFolder = $this->createMock(Folder::class);
		$receiver1UserFolder->method('get')->willReturn($receiver1RootFolder);

		$return[] = [ '/folder', ['secured-bar1.txt', 'bar2.txt'], $receiver1UserFolder, false ];

		// 4. cannot download zipped secure-shared folder
		$receiver2Folder = $this->createMock(Folder::class);
		$receiver2Folder->method('getStorage')->willReturn($secureSharedStorage);
		$receiver2RootFolder = $this->createMock(Folder::class);
		$receiver2RootFolder->method('getStorage')->willReturn($nonSharedStorage);
		$receiver2RootFolder->method('getDirectoryListing')->willReturn([$receiver2Folder]);
		$receiver2UserFolder = $this->createMock(Folder::class);
		$receiver2UserFolder->method('get')->willReturn($receiver2RootFolder);

		$return[] = [ '/', 'secured-folder', $receiver2UserFolder, false ];

		return $return;
	}

	/**
	 * @dataProvider providesDataForCanZip
	 */
	public function testCheckZipCanBeDownloaded($dir, $files, $userFolder, $run) {
		$user = $this->createMock(IUser::class);
		$user->method("getUID")->willReturn("test");
		$this->userSession->method("getUser")->willReturn($user);
		$this->userSession->method("isLoggedIn")->willReturn(true);

		$this->rootFolder->method('getUserFolder')->with("test")->willReturn($userFolder);

		// Simulate zip download of folder folder
		$event = new GenericEvent(null, ['dir' => $dir, 'files' => $files, 'run' => true]);
		$this->eventDispatcher->dispatch('file.beforeCreateZip', $event);

		$this->assertEquals($run, $event->getArgument('run'));
		$this->assertEquals($run, !$event->hasArgument('errorMessage'));
	}

	public function testCheckFileUserNotFound() {
		$this->userSession->method("isLoggedIn")->willReturn(false);

		// Simulate zip download of folder folder
		$event = new GenericEvent(null, ['dir' => '/test', 'files' => ['test.txt'], 'run' => true]);
		$this->eventDispatcher->dispatch('file.beforeCreateZip', $event);

		// It should run as this would restrict e.g. share links otherwise
		$this->assertTrue($event->getArgument('run'));
		$this->assertFalse($event->hasArgument('errorMessage'));
	}
}
