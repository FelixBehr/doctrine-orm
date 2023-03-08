<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal;

use Doctrine\ORM\Internal\TopologicalSort\CycleDetectedException;
use function array_reverse;

/**
 * TopologicalSort implements topological sorting, which is an ordering
 * algorithm for directed graphs (DG) using a depth-first searching (DFS)
 * to traverse the graph built in memory.
 * This algorithm have a linear running time based on nodes (V) and edges
 * between the nodes (E), resulting in a computational complexity of O(V + E).
 */
class TopologicalSort
{
    private const NOT_VISITED = 1;
    private const IN_PROGRESS = 2;
    private const VISITED = 3;

    /**
     * Array of all nodes, indexed by object ids.
     *
     * @var array<int, object>
     */
    private $nodes = [];

    /**
     * @var array<int, int>
     */
    private $states = [];

    /**
     * @var array<int, array<int, bool>>
     */
    private $edges = [];

    /**
     * Builds up the result during the DFS.
     *
     * @psalm-var list<object>
     */
    private $sortResult = [];

    /**
     * @param object $node
     */
    public function addNode($node): void
    {
        $id = spl_object_id($node);
        $this->nodes[$id] = $node;
        $this->states[$id] = self::NOT_VISITED;
        $this->edges[$id] = [];
    }

    /**
     * Adds a new edge between two nodes to the graph
     *
     * @param object $from
     * @param object $to
     */
    public function addEdge($from, $to, bool $optional): void
    {
        $fromId = spl_object_id($from);
        $toId = spl_object_id($to);

        if (isset($this->edges[$fromId][$toId]) && $this->edges[$fromId][$toId] === false) {
            return; // we already know about this dependency, and it is not optional
        }

        $this->edges[$fromId][$toId] = $optional;
    }

    /**
     * Returns a topological sort of all nodes. When we have an edge A->B between two nodes
     * A and B, then A will be listed before B in the result.
     *
     * @psalm-return list<object>
     */
    public function sort()
    {
        foreach (array_reverse(array_keys($this->nodes)) as $oid) {
            if ($this->states[$oid] === self::NOT_VISITED) {
                $this->visit($oid);
            }
        }

        return $this->sortResult;
    }

    private function visit(int $oid): void
    {
        if ($this->states[$oid] === self::IN_PROGRESS) {
            // This node is already on the current DFS stack. We've found a cycle!
            throw new CycleDetectedException($this->nodes[$oid]);
        }

        if ($this->states[$oid] === self::VISITED) {
            // We've reached a node that we've already seen, including all
            // other nodes that are reachable from here. We're done here, return.
            return;
        }

        $this->states[$oid] = self::IN_PROGRESS;

        // Continue the DFS downwards the edge list
        foreach ($this->edges[$oid] as $adjacentId => $optional) {
            try {
                $this->visit($adjacentId);
            } catch (CycleDetectedException $exception) {
                if ($exception->isCycleCollected()) {
                    // There is a complete cycle downstream of the current node. We cannot
                    // do anything about that anymore.
                    throw $exception;
                }

                if ($optional) {
                    // The current edge is part of a cycle, but it is optional and the closest
                    // such edge while backtracking. Break the cycle here by skipping the edge
                    // and continuing with the next one.
                    continue;
                }

                // We have found a cycle and cannot break it at $edge. Best we can do
                // is to retreat from the current vertex, hoping that somewhere up the
                // stack this can be salvaged.
                $this->states[$oid] = self::NOT_VISITED;
                $exception->addToCycle($this->nodes[$oid]);

                throw $exception;
            }
        }

        // We have traversed all edges and visited all other nodes reachable from here.
        // So we're done with this vertex as well.

        $this->states[$oid] = self::VISITED;
        array_unshift($this->sortResult, $this->nodes[$oid]);
    }
}
