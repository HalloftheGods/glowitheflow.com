// Polyfill ECMAScript 2024 Set methods for Node.js < 22
if (typeof Set !== 'undefined') {
  if (!Set.prototype.difference) {
    Set.prototype.difference = function (other) {
      const otherSet = other instanceof Set ? other : new Set(other);
      const result = new Set(this);
      for (const elem of otherSet) {
        result.delete(elem);
      }
      return result;
    };
  }
  if (!Set.prototype.intersection) {
    Set.prototype.intersection = function (other) {
      const otherSet = other instanceof Set ? other : new Set(other);
      const result = new Set();
      for (const elem of this) {
        if (otherSet.has(elem)) {
          result.add(elem);
        }
      }
      return result;
    };
  }
  if (!Set.prototype.union) {
    Set.prototype.union = function (other) {
      const otherSet = other instanceof Set ? other : new Set(other);
      const result = new Set(this);
      for (const elem of other) {
        result.add(elem);
      }
      return result;
    };
  }
  if (!Set.prototype.symmetricDifference) {
    Set.prototype.symmetricDifference = function (other) {
      const otherSet = other instanceof Set ? other : new Set(other);
      const result = new Set(this);
      for (const elem of otherSet) {
        if (result.has(elem)) {
          result.delete(elem);
        } else {
          result.add(elem);
        }
      }
      return result;
    };
  }
  if (!Set.prototype.isSubsetOf) {
    Set.prototype.isSubsetOf = function (other) {
      const otherSet = other instanceof Set ? other : new Set(other);
      if (this.size > otherSet.size) return false;
      for (const elem of this) {
        if (!otherSet.has(elem)) return false;
      }
      return true;
    };
  }
  if (!Set.prototype.isSupersetOf) {
    Set.prototype.isSupersetOf = function (other) {
      const otherSet = other instanceof Set ? other : new Set(other);
      if (this.size < otherSet.size) return false;
      for (const elem of otherSet) {
        if (!this.has(elem)) return false;
      }
      return true;
    };
  }
  if (!Set.prototype.isDisjointFrom) {
    Set.prototype.isDisjointFrom = function (other) {
      const otherSet = other instanceof Set ? other : new Set(other);
      for (const elem of this) {
        if (otherSet.has(elem)) return false;
      }
      return true;
    };
  }
}
