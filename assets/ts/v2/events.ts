export class EventConnector {

    /**
     * Searches for input/select/textarea nodes that have `data-map-value-to` and `data-map-value-as` to send their
     * values as data attributes to a different element.
     * `data-map-value-to` is expected to be a query string to select the nodes that the value is mapped to
     * `data-map-value-as` is expected to be the name of the data attribute to write to.
     * If the data source is a SELECT node, we also read data from OPTION child nodes. The option child nodes may use
     * `data-map-value-as` (data attribute name) and `data-value` (value to write). They may not define their own
     * target - instead, the target of the parent SELECT is used.
     * @param node
     * @private
     */
    private static dataValueTransferEvents(node: HTMLElement) {
        node.querySelectorAll('input[data-map-value-to][data-map-value-as],select[data-map-value-to][data-map-value-as],textarea[data-map-value-to][data-map-value-as]').forEach( (dataSource: HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement) =>
            dataSource.addEventListener("change", () => {
                // Cache the values that are to be sent
                let value_cache = {};

                // We always sent the native value of the source node as the given data attribute
                value_cache[dataSource.dataset.mapValueAs] = dataSource.value;

                // If the source node is a SELECT, we also check if the child options have data-value and data-map-value-as
                // For those that do, we add them to the value cache
                if (dataSource.nodeName === 'SELECT')
                    dataSource.querySelectorAll('option[data-map-value-as][data-value]:checked').forEach( (option: HTMLOptionElement) => {
                        value_cache[option.dataset.mapValueAs] = option.dataset.value;
                    } )

                // For each target node, apply the value cache
                document.querySelectorAll(dataSource.dataset.mapValueTo).forEach( (target: HTMLElement) => {
                    for (const [key, value] of Object.entries(value_cache))
                        target.dataset[key] = `${value}`;
                } )
            })
        )
    }

    /**
     * Searches for nodes that have `data-trigger-event-name`, triggers an event of the given name on the document
     * object and removed the node from the DOM. The node can optionally have a `data-trigger-event-data` attribute
     * containing JSON that is decoded and passed as the detail data with the event.
     * @param node
     * @private
     */
    private static domEventTriggerEvents(node: HTMLElement) {
        node.querySelectorAll('[data-trigger-event-name]').forEach( (eventNode: HTMLElement) => {
            const data = JSON.parse(eventNode.dataset.triggerEventData ?? 'null');
            const event = data
                ? new Event( eventNode.dataset.triggerEventName, {bubbles: false} )
                : new CustomEvent( eventNode.dataset.triggerEventName, {bubbles: false, detail: data} );

            document.dispatchEvent( event );
            eventNode.remove();
        } )
    }

    public static handle(node: HTMLElement) {
        this.domEventTriggerEvents(node);
        this.dataValueTransferEvents(node);
    }

}