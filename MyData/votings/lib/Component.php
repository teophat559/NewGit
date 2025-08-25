<?php
/**
 * Component System tương tự React
 * Hỗ trợ render, state management và lifecycle
 */
abstract class Component {
    protected $props = [];
    protected $state = [];
    protected $children = [];
    protected $id;

    public function __construct($props = []) {
        $this->props = $props;
        $this->id = uniqid('component_');
        $this->init();
    }

    /**
     * Khởi tạo component
     */
    protected function init() {
        // Override trong component con
    }

    /**
     * Set state
     */
    protected function setState($newState) {
        $this->state = array_merge($this->state, $newState);
        $this->render();
    }

    /**
     * Get state
     */
    protected function getState($key = null) {
        if ($key === null) {
            return $this->state;
        }
        return $this->state[$key] ?? null;
    }

    /**
     * Get props
     */
    protected function getProps($key = null) {
        if ($key === null) {
            return $this->props;
        }
        return $this->props[$key] ?? null;
    }

    /**
     * Add child component
     */
    public function addChild($child) {
        $this->children[] = $child;
        return $this;
    }

    /**
     * Render component
     */
    public function render() {
        ob_start();
        $this->renderContent();
        $content = ob_get_clean();

        // Wrap với component wrapper nếu cần
        if (method_exists($this, 'getWrapper')) {
            $wrapper = $this->getWrapper();
            return $wrapper($content);
        }

        return $content;
    }

    /**
     * Render nội dung chính - override trong component con
     */
    abstract protected function renderContent();

    /**
     * Get component ID
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Magic method để render component
     */
    public function __toString() {
        return $this->render();
    }
}

/**
 * Base Component cho UI elements
 */
abstract class UIComponent extends Component {
    protected $className = '';
    protected $style = '';
    protected $attributes = [];

    /**
     * Set CSS class
     */
    public function setClass($className) {
        $this->className = $className;
        return $this;
    }

    /**
     * Add CSS class
     */
    public function addClass($className) {
        $this->className .= ' ' . $className;
        return $this;
    }

    /**
     * Set inline style
     */
    public function setStyle($style) {
        $this->style = $style;
        return $this;
    }

    /**
     * Set HTML attribute
     */
    public function setAttribute($key, $value) {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Get HTML attributes string
     */
    protected function getAttributesString() {
        $attrs = [];

        if ($this->className) {
            $attrs[] = 'class="' . htmlspecialchars($this->className) . '"';
        }

        if ($this->style) {
            $attrs[] = 'style="' . htmlspecialchars($this->style) . '"';
        }

        foreach ($this->attributes as $key => $value) {
            $attrs[] = $key . '="' . htmlspecialchars($value) . '"';
        }

        return implode(' ', $attrs);
    }

    /**
     * Render children
     */
    protected function renderChildren() {
        $output = '';
        foreach ($this->children as $child) {
            if (is_string($child)) {
                $output .= $child;
            } elseif (is_object($child) && method_exists($child, 'render')) {
                $output .= $child->render();
            } else {
                $output .= htmlspecialchars($child);
            }
        }
        return $output;
    }
}

/**
 * Component Factory để tạo components
 */
class ComponentFactory {
    private static $components = [];

    /**
     * Đăng ký component
     */
    public static function register($name, $componentClass) {
        self::$components[$name] = $componentClass;
    }

    /**
     * Tạo component
     */
    public static function create($name, $props = []) {
        if (isset(self::$components[$name])) {
            $componentClass = self::$components[$name];
            return new $componentClass($props);
        }

        throw new Exception("Component '$name' không tồn tại");
    }

    /**
     * Kiểm tra component có tồn tại không
     */
    public static function exists($name) {
        return isset(self::$components[$name]);
    }

    /**
     * Lấy danh sách components đã đăng ký
     */
    public static function getRegistered() {
        return array_keys(self::$components);
    }
}

/**
 * Component Renderer
 */
class ComponentRenderer {
    /**
     * Render component với data
     */
    public static function render($component, $data = []) {
        if (is_string($component)) {
            $component = ComponentFactory::create($component, $data);
        }

        if ($component instanceof Component) {
            return $component->render();
        }

        return '';
    }

    /**
     * Render nhiều components
     */
    public static function renderMultiple($components, $data = []) {
        $output = '';
        foreach ($components as $component) {
            $output .= self::render($component, $data);
        }
        return $output;
    }
}
