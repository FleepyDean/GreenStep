// Simple event bus for component communication
const events = new Map()

export const eventBus = {
  on(event, callback) {
    if (!events.has(event)) {
      events.set(event, [])
    }
    events.get(event).push(callback)
  },
  
  off(event, callback) {
    if (events.has(event)) {
      const callbacks = events.get(event)
      const index = callbacks.indexOf(callback)
      if (index > -1) {
        callbacks.splice(index, 1)
      }
    }
  },
  
  emit(event, data) {
    if (events.has(event)) {
      events.get(event).forEach(callback => callback(data))
    }
  }
}
