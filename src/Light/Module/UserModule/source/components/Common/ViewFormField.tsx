import * as React from 'react'
import {observer} from 'mobx-react'
import {Group} from "../../classes/Group";
import {User} from "../../classes/User";

@observer
export default class ViewFormField extends React.Component<{userOrGroup:User|Group, fieldKey:string}, {}> {

    handleChange = (e:any) => {
        this.props.userOrGroup.onChange(this.props.fieldKey, e.target.value);
    };

    render() {
        
        const userOrGroup = this.props.userOrGroup;
        const fieldKey = this.props.fieldKey;

        return (
            <tr>
                <th><label htmlFor={"input" + fieldKey}>{fieldKey}</label></th>
                <td>
                    <input type="text"
                           id={"input" + fieldKey}
                           value={userOrGroup[fieldKey]}
                           onChange={this.handleChange}
                    />
                </td>
            </tr>
        )

    }

}