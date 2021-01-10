<?php

namespace SoosyzeCore\Node\Services;

use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Util\Util;

class NodeUser
{
    /**
     * @var \SoosyzeCore\System\Services\Alias
     */
    private $alias;

    /**
     * @var \Soosyze\Config
     */
    private $config;

    /**
     * @var \SoosyzeCore\Node\Hook\User
     */
    private $hookUser;

    /**
     * @var \SoosyzeCore\QueryBuilder\Services\Query
     */
    private $query;

    /**
     * @var \Soosyze\Components\Router\Router
     */
    private $router;

    /**
     * @var string
     */
    private $title = '';

    /**
     * @var \SoosyzeCore\User\Services\User
     */
    private $user;

    public function __construct(
        $alias,
        $config,
        $hookUser,
        $query,
        $router,
        $user
    ) {
        $this->alias    = $alias;
        $this->config   = $config;
        $this->hookUser = $hookUser;
        $this->query    = $query;
        $this->router   = $router;
        $this->user     = $user;
    }

    public function getNodesQuery()
    {
        $query = clone $this->query;

        return $query->from('node')
            ->leftJoin('node_type', 'type', 'node_type.node_type');
    }

    public function whereNodes(&$nodeQuery)
    {
        $publish    = $this->user->isGranted('node.show.published');
        $notPublish = $this->user->isGranted('node.show.not_published');

        if ($this->user->isGranted('node.administer') || ($publish && $notPublish)) {
            return $this;
        }

        $nodeTypes = $this->query->from('node_type')->fetchAll();
        foreach ($nodeTypes as $type) {
            $typePublish    = $publish || $this->user->isGranted('node.show.published.' . $type[ 'node_type' ]);
            $typeNotPublish = $notPublish || $this->user->isGranted('node.show.not_published.' . $type[ 'node_type' ]);

            if ($typePublish || $typeNotPublish) {
                $nodeQuery->orWhere(function ($query) use ($type, $typePublish, $typeNotPublish) {
                    $query->where('type', $type[ 'node_type' ])
                        ->where(function ($query) use ($typePublish, $typeNotPublish) {
                            if ($typePublish) {
                                $query->where('node_status_id', '==', 1);
                            }
                            if ($typeNotPublish) {
                                $query->orWhere('node_status_id', '!=', 1);
                            }
                        });
                });
            } else {
                $nodeQuery->where('type', '!=', $type[ 'node_type' ]);
            }
        }

        return $this;
    }

    public function hydrateNodesLinks(array &$nodes)
    {
        $nodeAdminister = $this->user->isGranted('node.administer');

        $user = $this->user->isConnected();

        foreach ($nodes as &$node) {
            $node[ 'link_view' ] = $this->router->makeRoute(
                'node/' . $node[ 'id' ] === $this->config->get('settings.path_index')
                ? ''
                : $this->alias->getAlias('node/' . $node[ 'id' ], 'node/' . $node[ 'id' ])
            );

            $nodeEdit = $this->hookUser->hookNodeEdited($node[ 'id' ], null, $user);

            if ($nodeAdminister || $this->user->isGrantedPermission($nodeEdit)) {
                $node[ 'link_edit' ] = $this->router->getRoute('node.edit', [
                    ':id_node' => $node[ 'id' ]
                ]);
            }

            $nodeClone = $this->hookUser->hookNodeClone($node[ 'id' ], null, $user);

            if ($nodeAdminister || $this->user->isGrantedPermission($nodeClone)) {
                $node[ 'link_clone' ] = $this->router->getRoute('node.clone', [
                    ':id_node' => $node[ 'id' ]
                ]);
            }

            $nodeRemove = $this->hookUser->hookNodeDeleted($node[ 'id' ], null, $user);

            if ($nodeAdminister || $this->user->isGrantedPermission($nodeRemove)) {
                $node[ 'link_remove' ] = $this->router->getRoute('node.api.remove', [
                    ':id_node' => $node[ 'id' ]
                ]);
            }

            $node[ 'title' ] = Util::strHighlight($this->title, $node[ 'title' ]);
        }
        unset($node);
    }

    public function getInfosUser(array $node)
    {
        if (empty($node[ 'user_id' ])) {
            return null;
        }

        $user = $this->query
            ->select('bio', 'firstname', 'picture', 'name', 'username')
            ->from('user')
            ->where('user_id', $node[ 'user_id' ])
            ->fetch();

        if ($this->user->isGranted('user.showed')) {
            $user[ 'link' ] = $this->router->getRoute('user.show', [
                ':id' => $node[ 'user_id' ]
            ]);
        }

        return $user;
    }

    public function orWhereNodesUser(&$nodeQuery, $userId)
    {
        if ($this->user->isGranted('node.show.own')) {
            $nodeQuery->orWhere('user_id', '===', $userId);
        }

        return $this;
    }

    public function orderNodes(&$nodeQuery, ServerRequestInterface $req)
    {
        $get = $req->getQueryParams();

        $nodeQuery->orderBy('sticky', 'desc');

        if (!empty($get[ 'order_by' ]) && in_array($get[ 'order_by' ], [
                'date_changed', 'node_status_id', 'title', 'type'
            ])) {
            $sort = !isset($get[ 'sort' ]) || $get[ 'sort' ] !== 'asc'
                ? 'desc'
                : 'asc';

            $nodeQuery->orderBy($get[ 'order_by' ], $sort);
        } else {
            $nodeQuery->orderBy('date_changed', 'desc');
        }

        return $this;
    }
}
